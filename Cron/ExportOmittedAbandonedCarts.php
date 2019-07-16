<?php
declare(strict_types=1);
/**
 */

namespace CommerceLeague\ActiveCampaign\Cron;

use CommerceLeague\ActiveCampaign\Helper\Config as ConfigHelper;
use CommerceLeague\ActiveCampaign\MessageQueue\Topics;
use CommerceLeague\ActiveCampaign\Model\ResourceModel\Quote\Collection as QuoteCollection;
use CommerceLeague\ActiveCampaign\Model\ResourceModel\Quote\CollectionFactory as QuoteCollectionFactory;
use Magento\Framework\MessageQueue\PublisherInterface;

/**
 * Class ExportOmittedAbandonedCarts
 */
class ExportOmittedAbandonedCarts implements CronInterface
{
    /**
     * @var ConfigHelper
     */
    private $configHelper;

    /**
     * @var QuoteCollectionFactory
     */
    private $quoteCollectionFactory;

    /**
     * @var PublisherInterface
     */
    private $publisher;

    /**
     * @param ConfigHelper $configHelper
     * @param QuoteCollectionFactory $quoteCollectionFactory
     * @param PublisherInterface $publisher
     */
    public function __construct(
        ConfigHelper $configHelper,
        QuoteCollectionFactory $quoteCollectionFactory,
        PublisherInterface $publisher
    ) {
        $this->configHelper = $configHelper;
        $this->quoteCollectionFactory = $quoteCollectionFactory;
        $this->publisher = $publisher;
    }

    /**
     * @throws \Exception
     */
    public function run(): void
    {
        if (!$this->configHelper->isEnabled() || !$this->configHelper->isAbandonedCartExportEnabled()) {
            return;
        }

        $quoteIds = $this->getQuoteIds();

        foreach ($quoteIds as $quoteId) {
            $this->publisher->publish(
                Topics::QUOTE_ABANDONED_CART_EXPORT,
                json_encode(['quote_id' => $quoteId])
            );
        }
    }

    /**
     * @return array
     * @throws \Exception
     */
    private function getQuoteIds(): array
    {
        /** @var QuoteCollection $quoteCollection */
        $quoteCollection = $this->quoteCollectionFactory->create();
        $quoteCollection->addAbandonedFilter();
        $quoteCollection->addOmittedFilter();

        return $quoteCollection->getAllIds();
    }
}
