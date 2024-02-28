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
    public function __construct(private readonly ConfigHelper $configHelper, private readonly PublisherInterface $publisher)
    {
    }

    /**
     * @inheritDoc
     */
    public function execute(Observer $observer): void
    {
        if (!$this->configHelper->isEnabled() || !$this->configHelper->isCustomerExportEnabled()) {
            return;
        }

        /** @var MagentoCustomer $magentoCustomer */
        $magentoCustomer = $observer->getEvent()->getData('customer');

        $this->publisher->publish(
            Topics::CUSTOMER_CUSTOMER_EXPORT,
            json_encode(['magento_customer_id' => $magentoCustomer->getId()], JSON_THROW_ON_ERROR)
        );
    }
}
