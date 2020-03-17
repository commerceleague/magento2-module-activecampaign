<?php
declare(strict_types=1);
/**
 */

namespace CommerceLeague\ActiveCampaign\Cron;


use CommerceLeague\ActiveCampaign\Helper\Config as ConfigHelper;
use CommerceLeague\ActiveCampaign\MessageQueue\Topics;
use CommerceLeague\ActiveCampaign\Model\ResourceModel\Customer\Collection as CustomerCollection;
use CommerceLeague\ActiveCampaign\Model\ResourceModel\Customer\CollectionFactory as CustomerCollectionFactory;
use Magento\Framework\MessageQueue\PublisherInterface;

/**
 * Class PublishOmittedCustomers
 */
class PublishOmittedCustomers implements CronInterface
{
    /**
     * @var ConfigHelper
     */
    private $configHelper;

    /**
     * @var CustomerCollectionFactory
     */
    private $customerCollectionFactory;

    /**
     * @var PublisherInterface
     */
    private $publisher;

    /**
     * @param ConfigHelper $configHelper
     * @param CustomerCollectionFactory $customerCollectionFactory
     * @param PublisherInterface $publisher
     */
    public function __construct(
        ConfigHelper $configHelper,
        CustomerCollectionFactory $customerCollectionFactory,
        PublisherInterface $publisher
    ) {
        $this->configHelper = $configHelper;
        $this->customerCollectionFactory = $customerCollectionFactory;
        $this->publisher = $publisher;
    }

    /**
     * @inheritDoc
     */
    public function run(): void
    {
        if (!$this->configHelper->isEnabled() || !$this->configHelper->isCustomerExportEnabled()) {
            return;
        }

        $customerIds = $this->getCustomerIds();

        foreach ($customerIds as $customerId) {
            $this->publisher->publish(
                Topics::CUSTOMER_CUSTOMER_EXPORT,
                json_encode(['magento_customer_id' => $customerId])
            );
        }
    }

    /**
     * @return array
     */
    private function getCustomerIds(): array
    {
        /** @var CustomerCollection $customerCollection */
        $customerCollection = $this->customerCollectionFactory->create();
        $customerCollection->addCustomerOmittedFilter();

        return $customerCollection->getAllIds();
    }
}
