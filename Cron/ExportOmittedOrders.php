<?php
declare(strict_types=1);
/**
 */

namespace CommerceLeague\ActiveCampaign\Cron;

use CommerceLeague\ActiveCampaign\Helper\Config as ConfigHelper;
use CommerceLeague\ActiveCampaign\MessageQueue\Topics;
use CommerceLeague\ActiveCampaign\Model\ResourceModel\Order\Collection as OrderCollection;
use CommerceLeague\ActiveCampaign\Model\ResourceModel\Order\CollectionFactory as OrderCollectionFactory;
use Magento\Framework\MessageQueue\PublisherInterface;

/**
 * Class ExportOmittedOrders
 */
class ExportOmittedOrders implements CronInterface
{
    /**
     * @var ConfigHelper
     */
    private $configHelper;

    /**
     * @var OrderCollectionFactory
     */
    private $orderCollectionFactory;

    /**
     * @var PublisherInterface
     */
    private $publisher;

    /**
     * @param ConfigHelper $configHelper
     * @param OrderCollectionFactory $orderCollectionFactory
     * @param PublisherInterface $publisher
     */
    public function __construct(
        ConfigHelper $configHelper,
        OrderCollectionFactory $orderCollectionFactory,
        PublisherInterface $publisher
    ) {
        $this->configHelper = $configHelper;
        $this->orderCollectionFactory = $orderCollectionFactory;
        $this->publisher = $publisher;
    }


    /**
     * @inheritDoc
     */
    public function run(): void
    {
        if (!$this->configHelper->isEnabled() || !$this->configHelper->isOrderExportEnabled()) {
            return;
        }

        $orderIds = $this->getOrderIds();

        foreach ($orderIds as $orderId) {
            $this->publisher->publish(
                Topics::SALES_ORDER_EXPORT,
                json_encode(['magento_order_id' => $orderId])
            );
        }
    }

    /**
     * @return array
     */
    public function getOrderIds(): array
    {
        /** @var OrderCollection $orderCollection */
        $orderCollection = $this->orderCollectionFactory->create();
        $orderCollection->addExcludeGuestFilter();
        $orderCollection->addOmittedFilter();

        return $orderCollection->getAllIds();
    }
}
