<?php
declare(strict_types=1);
/**
 */

namespace CommerceLeague\ActiveCampaign\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Quote\Model\ResourceModel\Quote\CollectionFactory as QuoteCollectionFactory;
use Magento\Quote\Model\ResourceModel\Quote\Collection as QuoteCollection;
use CommerceLeague\ActiveCampaign\Helper\Config as ConfigHelper;

/**
 * Class AbandonedCart
 */
class AbandonedCart extends AbstractHelper
{
    /**
     * @var QuoteCollectionFactory
     */
    private $quoteCollectionFactory;

    /**
     * @var ConfigHelper
     */
    private $configHelper;

    /**
     * @param Context $context
     * @param QuoteCollectionFactory $quoteCollectionFactory
     * @param Config $configHelper
     */
    public function __construct(
        Context $context,
        QuoteCollectionFactory $quoteCollectionFactory,
        ConfigHelper $configHelper
    ) {
        $this->quoteCollectionFactory = $quoteCollectionFactory;
        $this->configHelper = $configHelper;
        parent::__construct($context);
    }

    /**
     * @return QuoteCollection
     * @throws \Exception
     */
    public function getExportCollection(): QuoteCollection
    {
        /** @var QuoteCollection $quoteCollection */
        $quoteCollection = $this->quoteCollectionFactory->create();

        $quoteCollection->addFieldToFilter(
            'items_count',
            ['neq' => '0']
        )->addFieldToFilter(
            'main_table.is_active',
            '1'
        )->addFieldToFilter(
            'main_table.customer_id',
            ['neq' => null]
        );

        $minutes = (int)$this->configHelper->getAbandonedCartExportAfter();

        $maxUpdatedAtTime = new \DateTime('now', new \DateTimeZone('UTC'));
        $interval = new \DateInterval(sprintf('PT%sM', $minutes));
        $maxUpdatedAtTime->sub($interval);

        $quoteCollection->addFieldToFilter(
            'main_table.updated_at',
            ['lteq' => $maxUpdatedAtTime->format('Y-m-d H:i:s')]
        );

        $quoteCollection->getSelect()->joinLeft(
            $quoteCollection->getSelect()->getConnection()->getTableName('activecampaign_abandoned'),
            'main_table.entity_id = activecampaign_abandoned.quote_id'
        );

        $quoteCollection->addFieldToFilter(
            'activecampaign_abandoned.activecampaign_id',
            ['null' => true]
        );

        return $quoteCollection;
    }
}
