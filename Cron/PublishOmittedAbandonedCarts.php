<?php
declare(strict_types=1);
/**
 */

namespace CommerceLeague\ActiveCampaign\Cron;

use CommerceLeague\ActiveCampaign\Api\CronInterface;
use CommerceLeague\ActiveCampaign\Helper\Config as ConfigHelper;
use CommerceLeague\ActiveCampaign\MessageQueue\Topics;
use CommerceLeague\ActiveCampaign\Model\ResourceModel\Quote\CollectionFactory as QuoteCollectionFactory;
use CommerceLeague\ActiveCampaign\Model\ResourceModel\Quote\Collection as QuoteCollection;
use Exception;
use Magento\Framework\MessageQueue\PublisherInterface;
use Psr\Log\LoggerInterface;

/**
 * Class PublishOmittedAbandonedCarts
 */
class PublishOmittedAbandonedCarts implements CronInterface
{

    public function __construct(private readonly ConfigHelper           $configHelper,
                                private readonly QuoteCollectionFactory $quoteCollectionFactory,
                                private readonly PublisherInterface     $publisher,
                                private readonly LoggerInterface        $logger
    ) {
    }

    /**
     * @throws Exception
     */
    public function run(): void
    {
        if (!$this->configHelper->isEnabled() || !$this->configHelper->isAbandonedCartExportEnabled()) {
            return;
        }

        $quoteIds = $this->getQuoteIds();

        if ($this->configHelper->isRetryAllOmittedEnabled()) {
            $this->logger->warning(sprintf(
                'ActiveCampaign retry_all_omitted is ON — re-publishing %d omitted abandoned cart '
                . 'record(s) without the scope bound',
                count($quoteIds)
            ));
        }

        foreach ($quoteIds as $quoteId) {
            $this->publisher->publish(
                Topics::QUOTE_ABANDONED_CART_EXPORT,
                json_encode(['quote_id' => $quoteId], JSON_THROW_ON_ERROR)
            );
        }
    }

    /**
     * @return array<int, string>
     * @throws Exception
     */
    private function getQuoteIds(): array
    {
        /** @var QuoteCollection $quoteCollection */
        $quoteCollection = $this->quoteCollectionFactory->create();
        if (!$this->configHelper->isRetryAllOmittedEnabled()) {
            $quoteCollection->addAbandonedFilter();
        }
        $quoteCollection->addOmittedFilter();
        $quoteCollection->addNotDeadLetteredFilter();

        return $quoteCollection->getAllIds();
    }
}
