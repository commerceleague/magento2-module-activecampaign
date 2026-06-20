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
use CommerceLeague\ActiveCampaign\Model\Export\DuplicateNotFoundException;
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
        private readonly Client $client
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

        /** @var Quote $quote */
        $quote = $this->quoteFactory->create();
        $quote->loadByIdWithoutStore($message['quote_id']);

        if (!$quote->getId()) {
            $this->getLogger()->error(__('The Quote with the "%1" ID doesn\'t exist', $message['quote_id']));
            return;
        }

        $order   = $this->orderRepository->getOrCreateByMagentoQuoteId($quote->getId());
        $request = $this->abandonedCartRequestBuilder->build($quote);

        try {
            $apiResponse = $this->client->getOrderApi()->create(['ecomOrder' => $request]);

            if (!isset($apiResponse[self::RESPONSE_KEY_ORDER]['id'])) {
                $this->getLogger()->error(sprintf(
                    '%s: missing "%s.id" in API response for quote id "%s"; skipping save.',
                    static::class,
                    self::RESPONSE_KEY_ORDER,
                    $quote->getId()
                ));
                return;
            }

            $order->setActiveCampaignId($apiResponse[self::RESPONSE_KEY_ORDER]['id']);
            $this->orderRepository->save($order);
        } catch (UnprocessableEntityHttpException $e) {
            try {
                $outcome = $this->handleUnprocessableEntityHttpException($e, $request, self::RESPONSE_KEY_ORDER);
            } catch (UnprocessableEntityHttpException $duplicateLookupException) {
                $this->logUnprocessableEntityHttpException($duplicateLookupException, $request);
                return;
            }

            if ($outcome->isDuplicate() && isset($outcome->payload[self::RESPONSE_KEY_ORDER]['id'])) {
                $order->setActiveCampaignId((int)$outcome->payload[self::RESPONSE_KEY_ORDER]['id']);
                $this->orderRepository->save($order);
                return;
            }

            $this->logUnprocessableEntityHttpException($e, $request);
            return;
        } catch (HttpException $e) {
            $this->logException($e);
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
}
