<?php
declare(strict_types=1);
/**
 */

namespace CommerceLeague\ActiveCampaign\MessageQueue\Quote;

use CommerceLeague\ActiveCampaign\Api\AbandonedRepositoryInterface;
use CommerceLeague\ActiveCampaign\Gateway\Client;
use CommerceLeague\ActiveCampaign\Logger\Logger;
use CommerceLeague\ActiveCampaign\MessageQueue\ConsumerInterface;
use CommerceLeague\ActiveCampaign\Gateway\Request\AbandonedCartBuilder;
use CommerceLeague\ActiveCampaignApi\Exception\HttpException;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Quote\Model\Quote;
use Magento\Quote\Model\QuoteFactory;

/**
 * Class ExportAbandonedCartConsumer
 */
class ExportAbandonedCartConsumer implements ConsumerInterface
{
    /**
     * @var AbandonedRepositoryInterface
     */
    private $abandonedRepository;

    /**
     * @var Logger
     */
    private $logger;

    /**
     * @var QuoteFactory
     */
    private $quoteFactory;

    /**
     * @var AbandonedCartBuilder
     */
    private $abandonedCartRequestBuilder;

    /**
     * @var Client
     */
    private $client;

    /**
     * @param AbandonedRepositoryInterface $abandonedRepository
     * @param Logger $logger
     * @param QuoteFactory $quoteFactory
     * @param AbandonedCartBuilder $abandonedCartRequestBuilder
     * @param Client $client
     */
    public function __construct(
        AbandonedRepositoryInterface $abandonedRepository,
        Logger $logger,
        QuoteFactory $quoteFactory,
        AbandonedCartBuilder $abandonedCartRequestBuilder,
        Client $client
    ) {
        $this->abandonedRepository = $abandonedRepository;
        $this->logger = $logger;
        $this->quoteFactory = $quoteFactory;
        $this->abandonedCartRequestBuilder = $abandonedCartRequestBuilder;
        $this->client = $client;
    }

    /**
     * @param string $message
     * @throws CouldNotSaveException
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

        $abandoned = $this->abandonedRepository->getOrCreateByQuoteId($quote->getId());
        $request = $this->abandonedCartRequestBuilder->build($quote);

        try {
            $apiResponse = $this->client->getOrderApi()->create(['ecomOrder' => $request]);
        } catch (HttpException $e) {
            $this->logger->error($e->getMessage());
            return;
        }

        $abandoned->setActiveCampaignId($apiResponse['ecomOrder']['id']);
        $this->abandonedRepository->save($abandoned);
    }
}
