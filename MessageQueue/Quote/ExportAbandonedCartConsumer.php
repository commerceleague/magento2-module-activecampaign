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

    private readonly \CommerceLeague\ActiveCampaign\Logger\Logger $logger;

    /**
     * @param QuoteFactory             $quoteFactory
     * @param Logger                   $logger
     * @param OrderRepositoryInterface $orderRepository
     * @param AbandonedCartBuilder     $abandonedCartRequestBuilder
     * @param Client                   $client
     */
    public function __construct(
        QuoteFactory $quoteFactory,
        Logger $logger,
        private readonly OrderRepositoryInterface $orderRepository,
        private readonly AbandonedCartBuilder $abandonedCartRequestBuilder,
        private readonly Client $client
    ) {
        parent::__construct($logger);
        $this->quoteFactory                = $quoteFactory;
        $this->logger                      = $logger;
    }

    /**
     * @param string $message
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
            $this->logger->error(__('The Quote with the "%1" ID doesn\'t exist', $message['quote_id']));
            return;
        }

        $order   = $this->orderRepository->getOrCreateByMagentoQuoteId($quote->getId());
        $request = $this->abandonedCartRequestBuilder->build($quote);

        try {
            $apiResponse = $this->client->getOrderApi()->create(['ecomOrder' => $request]);
        } catch (UnprocessableEntityHttpException $e) {
            $this->logUnprocessableEntityHttpException($e, $request);
            return;
        } catch (HttpException $e) {
            $this->logException($e);
            return;
        }

        $order->setActiveCampaignId($apiResponse['ecomOrder']['id']);
        $this->orderRepository->save($order);
    }
}
