<?php
declare(strict_types=1);
/**
 */

namespace CommerceLeague\ActiveCampaign\Observer\Customer;

use CommerceLeague\ActiveCampaign\Helper\Config as ConfigHelper;
use CommerceLeague\ActiveCampaign\MessageQueue\Topics;
use Magento\Customer\Model\Customer as MagentoCustomer;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\MessageQueue\PublisherInterface;

/**
 * Class ExportCustomerObserver
 */
class ExportCustomerObserver implements ObserverInterface
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
        if (!$this->configHelper->isApiEnabled()) {
            return;
        }

        /** @var MagentoCustomer $magentoCustomer */
        $magentoCustomer = $observer->getEvent()->getData('customer');

        $this->publisher->publish(
            Topics::CUSTOMER_CUSTOMER_EXPORT,
            json_encode(['magento_customer_id' => $magentoCustomer->getId()])
        );
    }
}
