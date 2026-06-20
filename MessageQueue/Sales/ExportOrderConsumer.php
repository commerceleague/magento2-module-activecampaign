<?php
declare(strict_types=1);
/**
 */

namespace CommerceLeague\ActiveCampaign\MessageQueue\Sales;

use CommerceLeague\ActiveCampaign\Api\Data\GuestCustomerInterface;
use CommerceLeague\ActiveCampaign\Api\Data\OrderInterface;
use CommerceLeague\ActiveCampaign\Api\OrderRepositoryInterface;
use CommerceLeague\ActiveCampaign\Gateway\Client;
use CommerceLeague\ActiveCampaign\Gateway\Request\OrderBuilder as OrderRequestBuilder;
use CommerceLeague\ActiveCampaign\Logger\Logger;
use CommerceLeague\ActiveCampaign\MessageQueue\AbstractConsumer;
use CommerceLeague\ActiveCampaign\MessageQueue\ConsumerInterface;
use CommerceLeague\ActiveCampaign\MessageQueue\Topics;
use CommerceLeague\ActiveCampaign\Model\Export\BackoffState;
use CommerceLeague\ActiveCampaign\Model\Export\DuplicateNotFoundException;
use CommerceLeague\ActiveCampaign\Model\Export\FailureRecorder;
use CommerceLeague\ActiveCampaignApi\Exception\HttpException;
use CommerceLeague\ActiveCampaignApi\Exception\UnprocessableEntityHttpException;
use Exception;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\MessageQueue\PublisherInterface;
use Magento\Sales\Api\Data\OrderInterface as MagentoOrderInterface;
use Magento\Sales\Api\OrderRepositoryInterface as MagentoOrderRepositoryInterface;
use Magento\Sales\Model\Order as MagentoOrder;

/**
 * Class ExportOrderConsumer
 */
class ExportOrderConsumer extends AbstractConsumer implements ConsumerInterface
{

    /**
     * Maximum number of times an order export may be deferred while waiting for
     * its customer/guest to be exported to ActiveCampaign first.
     */
    private const MAX_DEFERRALS = 1;

    public function __construct(
        private readonly MagentoOrderRepositoryInterface $magentoOrderRepository,
        Logger $logger,
        private readonly OrderRepositoryInterface $orderRepository,
        private readonly OrderRequestBuilder $orderRequestBuilder,
        private readonly Client $client,
        private readonly PublisherInterface $publisher,
        private readonly FailureRecorder $failureRecorder,
        private readonly BackoffState $backoffState
    ) {
        parent::__construct($logger);
    }

    /**
     *
     * @throws CouldNotSaveException
     * @throws Exception
     */
    public function consume(string $message): void
    {
        $message = json_decode($message, true, 512, JSON_THROW_ON_ERROR);

        if ($this->backoffState->shouldHalt()) {
            $this->getLogger()->warning('ActiveCampaign export backing off after repeated 503s; skipping');
            return;
        }

        try {
            /** @var MagentoOrderInterface|MagentoOrder $magentoOrder */
            $magentoOrder = $this->magentoOrderRepository->get($message['magento_order_id']);
        } catch (NoSuchEntityException $e) {
            $this->logException($e);
            return;
        }

        $order = $this->orderRepository->getOrCreateByMagentoQuoteId($magentoOrder->getQuoteId());

        try {
            $request = $this->orderRequestBuilder->build($magentoOrder);
        } catch (\Throwable $e) {
            $this->getLogger()->error(sprintf(
                '%s: failed to build request for Magento order id "%s": %s',
                static::class,
                $message['magento_order_id'],
                $e->getMessage()
            ));
            $this->failureRecorder->recordFailure($order, 'builder_error', $e->getMessage());
            $this->orderRepository->save($order);
            return;
        }

        if (empty($request['customerid'])) {
            $deferredCount = (int)($message['deferred_count'] ?? 0);
            if ($deferredCount < self::MAX_DEFERRALS) {
                // Publish the dependency export first so the customer/guest gets an AC id.
                if ($magentoOrder->getCustomerIsGuest()) {
                    $this->publisher->publish(Topics::GUEST_CUSTOMER_EXPORT, json_encode([
                        'magento_customer_id' => null,
                        'customer_is_guest'   => true,
                        'customer_data'       => [
                            GuestCustomerInterface::FIRSTNAME => $magentoOrder->getCustomerFirstname(),
                            GuestCustomerInterface::LASTNAME  => $magentoOrder->getCustomerLastname(),
                            GuestCustomerInterface::EMAIL     => $magentoOrder->getCustomerEmail(),
                        ],
                    ], JSON_THROW_ON_ERROR));
                } else {
                    $this->publisher->publish(Topics::CUSTOMER_CUSTOMER_EXPORT, json_encode([
                        'magento_customer_id' => $magentoOrder->getCustomerId(),
                    ], JSON_THROW_ON_ERROR));
                }

                // Re-queue the order with an incremented deferral counter.
                $this->publisher->publish(Topics::SALES_ORDER_EXPORT, json_encode([
                    'magento_order_id' => $message['magento_order_id'],
                    'deferred_count'   => $deferredCount + 1,
                ], JSON_THROW_ON_ERROR));

                $this->getLogger()->info(sprintf(
                    'Order %s deferred: customer not yet exported to ActiveCampaign',
                    $message['magento_order_id']
                ));
                return;
            }

            // Deferral cap reached: do NOT send customerid:null; log and stop (Phase 5 will track this).
            $this->getLogger()->error(sprintf(
                'Order %s still has no ActiveCampaign customer id after deferral; skipping to avoid field_missing',
                $message['magento_order_id']
            ));
            return;
        }

        try {
            $apiResponse = $this->performApiRequest($order, $request);

            $activeCampaignId = $this->extractActiveCampaignId($apiResponse[self::RESPONSE_KEY_ORDER]['id'] ?? null);
            if ($activeCampaignId === null) {
                $this->getLogger()->error(sprintf(
                    '%s: missing "%s.id" in API response for Magento order id "%s"; skipping save.',
                    static::class,
                    self::RESPONSE_KEY_ORDER,
                    $message['magento_order_id']
                ));
                $this->failureRecorder->recordFailure($order, 'empty_response', null);
                $this->orderRepository->save($order);
                return;
            }

            $order->setActiveCampaignId($activeCampaignId);
            $order->setMagentoOrderId($magentoOrder->getEntityId());

            $this->backoffState->reset();
            $this->failureRecorder->recordSuccess($order);
            $this->orderRepository->save($order);
        } catch (UnprocessableEntityHttpException $e) {
            try {
                $outcome = $this->handleUnprocessableEntityHttpException($e, $request, self::RESPONSE_KEY_ORDER);
            } catch (UnprocessableEntityHttpException $duplicateLookupException) {
                $this->logUnprocessableEntityHttpException($duplicateLookupException, $request);
                return;
            }

            $duplicateId = $outcome->isDuplicate()
                ? $this->extractActiveCampaignId($outcome->payload[self::RESPONSE_KEY_ORDER]['id'] ?? null)
                : null;
            if ($duplicateId !== null) {
                $order->setActiveCampaignId($duplicateId);
                $order->setMagentoOrderId($magentoOrder->getEntityId());
                $this->backoffState->reset();
                $this->failureRecorder->recordSuccess($order);
                $this->orderRepository->save($order);
                return;
            }

            $this->logUnprocessableEntityHttpException($e, $request);
            $this->failureRecorder->recordFailure($order, $outcome->code ?? 'unknown', $outcome->message);
            $this->orderRepository->save($order);
            return;
        } catch (HttpException $e) {
            if ($e->getCode() === 503) {
                $this->backoffState->record503();
            }
            $this->logException($e);
            $transient = $e->getCode() >= 500;
            $this->failureRecorder->recordFailure($order, 'http_error', $e->getMessage(), $transient);
            $this->orderRepository->save($order);
            return;
        }

    }

    /**
     * @inheritDoc
     */
    function processDuplicateEntity(array $request, string $key): array
    {
        $response = $this->client->getOrderApi()->listPerPage(
            1,
            0,
            [
                'filters' => [
                    'externalid' => $request['externalid']
                ]
            ]
        );

        $items = $response->getItems();
        if ($items === []) {
            throw new DuplicateNotFoundException();
        }

        return [$key => $items[0]];
    }

    private function performApiRequest(OrderInterface $order, array $request): array
    {
        if ($activeCampaignId = $order->getActiveCampaignId()) {
            return $this->client->getOrderApi()->update((int)$activeCampaignId, ['ecomOrder' => $request]);
        } else {
            return $this->client->getOrderApi()->create(['ecomOrder' => $request]);
        }
    }
}
