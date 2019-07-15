<?php
declare(strict_types=1);
/**
 */

namespace CommerceLeague\ActiveCampaign\Observer\Sales;

use CommerceLeague\ActiveCampaign\Helper\Config as ConfigHelper;
use CommerceLeague\ActiveCampaign\MessageQueue\Topics;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\MessageQueue\PublisherInterface;
use Magento\Sales\Model\Order as MagentoOrder;

/**
 * Class ExportOrderObserver
 */
class ExportOrderObserver implements ObserverInterface
{
    /**
     * @var ConfigHelper
     */
    private $configHelper;

    /**
     * @var PublisherInterface
     */
    private $publisher;

    /**
     * @param ConfigHelper $configHelper
     * @param PublisherInterface $publisher
     */
    public function __construct(
        ConfigHelper $configHelper,
        PublisherInterface $publisher
    ) {
        $this->configHelper = $configHelper;
        $this->publisher = $publisher;
    }

    /**
     * @inheritDoc
     */
    public function execute(Observer $observer)
    {
        if (!$this->configHelper->isEnabled() || !$this->configHelper->isOrderExportEnabled()) {
            return;
        }

        /** @var MagentoOrder $magentoOrder */
        $magentoOrder = $observer->getEvent()->getData('order');

        if ($magentoOrder->getCustomerIsGuest()) {
            return;
        }

        $this->publisher->publish(
            Topics::SALES_ORDER_EXPORT,
            json_encode(['magento_order_id' => $magentoOrder->getId()])
        );
    }
}
