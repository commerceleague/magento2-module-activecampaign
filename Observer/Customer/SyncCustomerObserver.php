<?php
declare(strict_types=1);
/**
 */

namespace CommerceLeague\ActiveCampaign\Observer\Customer;

use CommerceLeague\ActiveCampaign\Gateway\Request\CustomerBuilder as CustomerRequestBuilder;
use CommerceLeague\ActiveCampaign\Helper\Config as ConfigHelper;
use CommerceLeague\ActiveCampaign\MessageQueue\Topics;
use Magento\Customer\Model\Customer;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\MessageQueue\PublisherInterface;

/**
 * Class SyncCustomerObserver
 */
class SyncCustomerObserver implements ObserverInterface
{
    /**
     * @var ConfigHelper
     */
    private $configHelper;

    /**
     * @var CustomerRequestBuilder
     */
    private $customerRequestBuilder;

    /**
     * @var PublisherInterface
     */
    private $publisher;

    /**
     * @param ConfigHelper $configHelper
     * @param CustomerRequestBuilder $customerRequestBuilder
     * @param PublisherInterface $publisher
     */
    public function __construct(
        ConfigHelper $configHelper,
        CustomerRequestBuilder $customerRequestBuilder,
        PublisherInterface $publisher
    ) {
        $this->configHelper = $configHelper;
        $this->customerRequestBuilder = $customerRequestBuilder;
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

        /** @var Customer $magentoCustomer */
        $magentoCustomer = $observer->getEvent()->getData('customer');

        $data = [
            'magento_customer_id' => $magentoCustomer->getId(),
            'request' => $this->customerRequestBuilder->build($magentoCustomer)
        ];

        $this->publisher->publish(Topics::CUSTOMER_SYNC, json_encode($data));
    }
}
