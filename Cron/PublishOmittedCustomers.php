<?php
declare(strict_types=1);
/**
 */

namespace CommerceLeague\ActiveCampaign\Cron;

use CommerceLeague\ActiveCampaign\Api\CronInterface;
use CommerceLeague\ActiveCampaign\Helper\Config as ConfigHelper;
use CommerceLeague\ActiveCampaign\MessageQueue\Topics;
use CommerceLeague\ActiveCampaign\Model\ResourceModel\Customer\CollectionFactory as CustomerCollectionFactory;
use CommerceLeague\ActiveCampaign\vendor\Model\ResourceModel\Customer\Collection as CustomerCollection;
use Magento\Framework\MessageQueue\PublisherInterface;

/**
 * Class PublishOmittedCustomers
 */
class PublishOmittedCustomers implements CronInterface
{

    public function __construct(private readonly ConfigHelper              $configHelper,
                                private readonly CustomerCollectionFactory $customerCollectionFactory,
                                private readonly PublisherInterface        $publisher
    ) {
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
                json_encode(['magento_customer_id' => $customerId], JSON_THROW_ON_ERROR)
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
