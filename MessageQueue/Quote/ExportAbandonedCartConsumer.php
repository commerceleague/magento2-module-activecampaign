<?php
declare(strict_types=1);
/**
 */

namespace CommerceLeague\ActiveCampaign\MessageQueue\Quote;

use CommerceLeague\ActiveCampaign\Api\OrderRepositoryInterface;
use CommerceLeague\ActiveCampaign\Gateway\Client;
use CommerceLeague\ActiveCampaign\Logger\Logger;
use CommerceLeague\ActiveCampaign\MessageQueue\AbstractConsumer;
use CommerceLeague\ActiveCampaign\MessageQueue\ConsumerInterface;
use CommerceLeague\ActiveCampaign\Gateway\Request\AbandonedCartBuilder;
use CommerceLeague\ActiveCampaign\Model\Export\BackoffState;
use CommerceLeague\ActiveCampaign\Model\Export\DuplicateNotFoundException;
use CommerceLeague\ActiveCampaign\Model\Export\FailureRecorder;
use CommerceLeague\ActiveCampaignApi\Exception\HttpException;
use CommerceLeague\ActiveCampaignApi\Exception\UnprocessableEntityHttpException;
use Exception;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Quote\Model\Quote;
use Magento\Quote\Model\QuoteFactory;

/**
 * Class ExportAbandonedCartConsumer
 */
class ExportAbandonedCartConsumer extends AbstractConsumer implements ConsumerInterface
{

    /**
     * @var QuoteFactory
     */
    private $quoteFactory;

    /**
     * @param QuoteFactory             $quoteFactory
     */
    public function __construct(
        QuoteFactory $quoteFactory,
        Logger $logger,
        private readonly OrderRepositoryInterface $orderRepository,
        private readonly AbandonedCartBuilder $abandonedCartRequestBuilder,
        private readonly Client $client,
        private readonly FailureRecorder $failureRecorder,
        private readonly BackoffState $backoffState
    ) {
        parent::__construct($logger);
        $this->quoteFactory = $quoteFactory;
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

        /** @var Quote $quote */
        $quote = $this->quoteFactory->create();
        $quote->loadByIdWithoutStore($message['quote_id']);

        if (!$quote->getId()) {
            $this->getLogger()->error(__('The Quote with the "%1" ID doesn\'t exist', $message['quote_id']));
            return;
        }

        try {
            $order = $this->orderRepository->getOrCreateByMagentoQuoteId($quote->getId());

            try {
                $request = $this->abandonedCartRequestBuilder->build($quote);
            } catch (\Throwable $e) {
                $this->logFailure(
                    'abandoned_cart',
                    $this->castId($order->getId()),
                    $this->castId($message['quote_id']),
                    null,
                    'builder_error',
                    $e->getMessage()
                );
                $this->failureRecorder->recordFailure($order, 'builder_error', $e->getMessage());
                $this->orderRepository->save($order);
                return;
            }

            try {
                $apiResponse = $this->client->getOrderApi()->create(['ecomOrder' => $request]);

                $activeCampaignId = $this->extractActiveCampaignId(
                    $apiResponse[self::RESPONSE_KEY_ORDER]['id'] ?? null
                );
                if ($activeCampaignId === null) {
                    $this->logFailure(
                        'abandoned_cart',
                        $this->castId($order->getId()),
                        $this->castId($message['quote_id']),
                        null,
                        'empty_response',
                        sprintf('missing "%s.id" in API response; skipping save', self::RESPONSE_KEY_ORDER)
                    );
                    $this->failureRecorder->recordFailure($order, 'empty_response', null);
                    $this->orderRepository->save($order);
                    return;
                }

                $order->setActiveCampaignId($activeCampaignId);
                $this->backoffState->reset();
                $this->failureRecorder->recordSuccess($order);
                $this->orderRepository->save($order);
            } catch (UnprocessableEntityHttpException $e) {
                try {
                    $outcome = $this->handleUnprocessableEntityHttpException($e, $request, self::RESPONSE_KEY_ORDER);
                } catch (UnprocessableEntityHttpException $duplicateLookupException) {
                    $this->logFailure(
                        'abandoned_cart',
                        $this->castId($order->getId()),
                        $this->castId($message['quote_id']),
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
                    $order->setActiveCampaignId($duplicateId);
                    $this->backoffState->reset();
                    $this->failureRecorder->recordSuccess($order);
                    $this->orderRepository->save($order);
                    return;
                }

                $this->logFailure(
                    'abandoned_cart',
                    $this->castId($order->getId()),
                    $this->castId($message['quote_id']),
                    $e->getCode() ?: 422,
                    $outcome->code ?? 'unknown',
                    $outcome->message
                );
                $this->failureRecorder->recordFailure($order, $outcome->code ?? 'unknown', $outcome->message);
                $this->orderRepository->save($order);
                return;
            } catch (HttpException $e) {
                if ($e->getCode() === 503) {
                    $this->backoffState->record503();
                }
                $this->logFailure(
                    'abandoned_cart',
                    $this->castId($order->getId()),
                    $this->castId($message['quote_id']),
                    $e->getCode(),
                    'http_error',
                    $e->getMessage()
                );
                $transient = $e->getCode() >= 500;
                $this->failureRecorder->recordFailure($order, 'http_error', $e->getMessage(), $transient);
                $this->orderRepository->save($order);
                return;
            }
        } catch (\Throwable $t) {
            $localId = isset($order) ? $this->castId($order->getId()) : null;
            $this->logFailure(
                'abandoned_cart',
                $localId,
                $this->castId($message['quote_id'] ?? null),
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
                    'externalcheckoutid' => $request['externalcheckoutid']
                ]
            ]
        );

        $items = $response->getItems();
        if ($items === []) {
            throw new DuplicateNotFoundException();
        }

        return [$key => $items[0]];
    }
}
