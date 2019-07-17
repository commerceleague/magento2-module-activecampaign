<?php
declare(strict_types=1);
/**
 */

namespace CommerceLeague\ActiveCampaign\MessageQueue\Quote;

use CommerceLeague\ActiveCampaign\Api\OrderRepositoryInterface;
use CommerceLeague\ActiveCampaign\Gateway\Client;
use CommerceLeague\ActiveCampaign\Logger\Logger;
use CommerceLeague\ActiveCampaign\MessageQueue\ConsumerInterface;
use CommerceLeague\ActiveCampaign\Gateway\Request\AbandonedCartBuilder;
use CommerceLeague\ActiveCampaignApi\Exception\HttpException;
use CommerceLeague\ActiveCampaignApi\Exception\UnprocessableEntityHttpException;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Quote\Model\Quote;
use Magento\Quote\Model\QuoteFactory;

/**
 * Class ExportAbandonedCartConsumer
 */
class ExportAbandonedCartConsumer implements ConsumerInterface
{
    /**
     * @var QuoteFactory
     */
    private $quoteFactory;

    /**
     * @var Logger
     */
    private $logger;

    /**
     * @var OrderRepositoryInterface
     */
    private $orderRepository;

    /**
     * @var AbandonedCartBuilder
     */
    private $abandonedCartRequestBuilder;

    /**
     * @var Client
     */
    private $client;

    /**
     * @param QuoteFactory $quoteFactory
     * @param Logger $logger
     * @param OrderRepositoryInterface $orderRepository
     * @param AbandonedCartBuilder $abandonedCartRequestBuilder
     * @param Client $client
     */
    public function __construct(
        QuoteFactory $quoteFactory,
        Logger $logger,
        OrderRepositoryInterface $orderRepository,
        AbandonedCartBuilder $abandonedCartRequestBuilder,
        Client $client
    ) {
        $this->quoteFactory = $quoteFactory;
        $this->logger = $logger;
        $this->orderRepository = $orderRepository;
        $this->abandonedCartRequestBuilder = $abandonedCartRequestBuilder;
        $this->client = $client;
    }

    /**
     * @param string $message
     * @throws CouldNotSaveException
     * @throws \Exception
     */
    public function consume(string $message): void
    {
        $message = json_decode($message, true);

        /** @var Quote $quote */
        $quote = $this->quoteFactory->create();
        $quote->loadByIdWithoutStore($message['quote_id']);

        if (!$quote->getId()) {
            $this->logger->error(__('The Quote with the "%1" ID doesn\'t exist', $message['quote_id']));
            return;
        }

        $order = $this->orderRepository->getOrCreateByMagentoQuoteId($quote->getId());
        $request = $this->abandonedCartRequestBuilder->build($quote);

        try {
            $apiResponse = $this->client->getOrderApi()->create(['ecomOrder' => $request]);
        } catch (UnprocessableEntityHttpException $e) {
            $this->logger->error($e->getMessage());
            $this->logger->error(print_r($e->getResponseErrors(), true));
            return;
        } catch (HttpException $e) {
            $this->logger->error($e->getMessage());
            return;
        }

        $order->setActiveCampaignId($apiResponse['ecomOrder']['id']);
        $this->orderRepository->save($order);
    }
}
