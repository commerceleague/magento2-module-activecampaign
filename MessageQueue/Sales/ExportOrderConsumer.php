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
use CommerceLeague\ActiveCampaignApi\Exception\BadRequestHttpException;
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
    private const MAX_DEFERRALS = 3;

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

        try {
            $order = $this->orderRepository->getOrCreateByMagentoQuoteId($magentoOrder->getQuoteId());

            try {
                $request = $this->orderRequestBuilder->build($magentoOrder);
            } catch (\Throwable $e) {
                $this->logFailure(
                    'order',
                    $this->castId($order->getId()),
                    $this->castId($message['magento_order_id']),
                    null,
                    'builder_error',
                    $e->getMessage()
                );
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

                // Deferral cap reached: do NOT send customerid:null. Track the order as a
                // recorded failure so it becomes visible in activecampaign:export:status
                // instead of remaining a permanent NULL (Issue D1).
                $failureMessage = sprintf(
                    'customer/guest never synced to AC after %d deferrals',
                    self::MAX_DEFERRALS
                );
                $this->logFailure(
                    'order',
                    $this->castId($order->getId()),
                    $this->castId($message['magento_order_id']),
                    null,
                    'customer_unresolved',
                    $failureMessage
                );
                $this->failureRecorder->recordFailure($order, 'customer_unresolved', $failureMessage);
                $this->orderRepository->save($order);
                return;
            }

            try {
                $apiResponse = $this->performApiRequest($order, $request);

                $activeCampaignId = $this->extractActiveCampaignId(
                    $apiResponse[self::RESPONSE_KEY_ORDER]['id'] ?? null
                );
                if ($activeCampaignId === null) {
                    $this->logFailure(
                        'order',
                        $this->castId($order->getId()),
                        $this->castId($message['magento_order_id']),
                        null,
                        'empty_response',
                        sprintf('missing "%s.id" in API response; skipping save', self::RESPONSE_KEY_ORDER)
                    );
                    $this->failureRecorder->recordFailure($order, 'empty_response', null);
                    $this->orderRepository->save($order);
                    return;
                }

                $this->recordExportSuccess($order, $magentoOrder, $activeCampaignId);
            } catch (UnprocessableEntityHttpException $e) {
                try {
                    $outcome = $this->handleUnprocessableEntityHttpException($e, $request, self::RESPONSE_KEY_ORDER);
                } catch (UnprocessableEntityHttpException $duplicateLookupException) {
                    $this->logFailure(
                        'order',
                        $this->castId($order->getId()),
                        $this->castId($message['magento_order_id']),
                        $duplicateLookupException->getCode(),
                        'http_error',
                        $duplicateLookupException->getMessage()
                    );
                    return;
                }

                $duplicateId = $outcome->isDuplicate()
                    ? $this->extractActiveCampaignId($outcome->payload[self::RESPONSE_KEY_ORDER]['id'] ?? null)
                    : null;
                if ($duplicateId !== null) {
                    $this->recordExportSuccess($order, $magentoOrder, $duplicateId);
                    return;
                }

                $this->logFailure(
                    'order',
                    $this->castId($order->getId()),
                    $this->castId($message['magento_order_id']),
                    $e->getCode() ?: 422,
                    $outcome->code ?? 'unknown',
                    $outcome->message
                );
                $this->failureRecorder->recordFailure($order, $outcome->code ?? 'unknown', $outcome->message);
                $this->orderRepository->save($order);
                return;
            } catch (BadRequestHttpException $e) {
                try {
                    $resolved = $this->processDuplicateEntity($request, self::RESPONSE_KEY_ORDER);
                    $duplicateId = $this->extractActiveCampaignId($resolved[self::RESPONSE_KEY_ORDER]['id'] ?? null);
                } catch (\Throwable $lookupError) {
                    $duplicateId = null;
                }

                if ($duplicateId === null || $duplicateId === (int)$order->getActiveCampaignId()) {
                    $this->logFailure(
                        'order',
                        $this->castId($order->getId()),
                        $this->castId($message['magento_order_id']),
                        $e->getCode(),
                        'http_error',
                        $e->getMessage()
                    );
                    $this->failureRecorder->recordFailure($order, 'http_error', $e->getMessage());
                    $this->orderRepository->save($order);
                    return;
                }

                // Another AC record already owns this externalid (typically after a database
                // refresh reused Magento entity ids). Re-link and apply the update there.
                $order->setActiveCampaignId($duplicateId);
                $apiResponse = $this->performApiRequest($order, $request);

                $activeCampaignId = $this->extractActiveCampaignId(
                    $apiResponse[self::RESPONSE_KEY_ORDER]['id'] ?? null
                );
                $this->recordExportSuccess($order, $magentoOrder, $activeCampaignId);
                return;
            } catch (HttpException $e) {
                if ($e->getCode() === 503) {
                    $this->backoffState->record503();
                }
                $this->logFailure(
                    'order',
                    $this->castId($order->getId()),
                    $this->castId($message['magento_order_id']),
                    $e->getCode(),
                    'http_error',
                    $e->getMessage()
                );
                $transient = $e->getCode() >= 500 || $e->getCode() === 429;
                $this->failureRecorder->recordFailure($order, 'http_error', $e->getMessage(), $transient);
                $this->orderRepository->save($order);
                return;
            }
        } catch (\Throwable $t) {
            $localId = isset($order) ? $this->castId($order->getId()) : null;
            $this->logFailure(
                'order',
                $localId,
                $this->castId($message['magento_order_id'] ?? null),
                null,
                'unexpected_error',
                $t->getMessage()
            );
            if (isset($order)) {
                $this->failureRecorder->recordFailure($order, 'unexpected_error', $t->getMessage());
                $this->orderRepository->save($order);
            }
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

        $item = $items[0];
        if ((string)$item['externalid'] !== (string)$request['externalid']) {
            throw new DuplicateNotFoundException();
        }

        return [$key => $item];
    }

    /**
     * Links the local row to the resolved ActiveCampaign id (when one is present —
     * the duplicate-recovery paths call this after already resolving a valid id
     * elsewhere and tolerate an absent one here), stamps the Magento order id,
     * resets backoff, and persists the success.
     *
     * @param OrderInterface $order the local ActiveCampaign order row
     * @param MagentoOrderInterface $magentoOrder the source Magento order
     * @param int|null $activeCampaignId the resolved ActiveCampaign order id, if any
     */
    private function recordExportSuccess(
        OrderInterface $order,
        MagentoOrderInterface $magentoOrder,
        ?int $activeCampaignId
    ): void {
        if ($activeCampaignId !== null) {
            $order->setActiveCampaignId($activeCampaignId);
        }
        $order->setMagentoOrderId($magentoOrder->getEntityId());

        $this->backoffState->reset();
        $this->failureRecorder->recordSuccess($order);
        $this->orderRepository->save($order);
    }

    /**
     * @param array<string, mixed> $request
     * @return array<string, mixed>
     */
    private function performApiRequest(OrderInterface $order, array $request): array
    {
        if ($activeCampaignId = $order->getActiveCampaignId()) {
            // The record may have started life as an abandoned cart (externalcheckoutid).
            // Clearing that id and abandonedDate while externalid is set makes ActiveCampaign
            // convert the record to a completed order and mark the cart recovered.
            $request['externalcheckoutid'] = null;
            $request['abandonedDate'] = null;

            return $this->client->getOrderApi()->update((int)$activeCampaignId, ['ecomOrder' => $request]);
        }

        return $this->client->getOrderApi()->create(['ecomOrder' => $request]);
    }
}
