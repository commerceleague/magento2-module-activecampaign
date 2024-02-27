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
 * Class ExportContactObserver
 */
class ExportContactObserver implements ObserverInterface
{

    public function __construct(private readonly ConfigHelper $configHelper, private readonly PublisherInterface $publisher)
    {
    }

    /**
     * @param Observer $observer
     */
    public function execute(Observer $observer)
    {
        if (!$this->configHelper->isEnabled() || !$this->configHelper->isContactExportEnabled()) {
            return;
        }

        /** @var MagentoCustomer $magentoCustomer */
        $magentoCustomer = $observer->getEvent()->getData('customer');

        $this->publisher->publish(
            Topics::CUSTOMER_CONTACT_EXPORT,
            json_encode(['magento_customer_id' => $magentoCustomer->getId()], JSON_THROW_ON_ERROR)
        );
    }
}
