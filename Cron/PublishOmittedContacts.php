<?php
declare(strict_types=1);
/**
 */

namespace CommerceLeague\ActiveCampaign\Cron;

use CommerceLeague\ActiveCampaign\Api\CronInterface;
use CommerceLeague\ActiveCampaign\Helper\Config as ConfigHelper;
use CommerceLeague\ActiveCampaign\MessageQueue\Topics;
use CommerceLeague\ActiveCampaign\Model\ResourceModel\Customer\CollectionFactory as CustomerCollectionFactory;
use CommerceLeague\ActiveCampaign\Model\ResourceModel\Subscriber\CollectionFactory as SubscriberCollectionFactory;
use CommerceLeague\ActiveCampaign\vendor\Model\ResourceModel\Customer\Collection as CustomerCollection;
use CommerceLeague\ActiveCampaign\vendor\Model\ResourceModel\Subscriber\Collection as SubscriberCollection;
use Magento\Framework\MessageQueue\PublisherInterface;

/**
 * Class ExportOmittedContacts
 */
class PublishOmittedContacts implements CronInterface
{

    public function __construct(private readonly ConfigHelper                $configHelper,
                                private readonly CustomerCollectionFactory   $customerCollectionFactory,
                                private readonly SubscriberCollectionFactory $subscriberCollectionFactory,
                                private readonly PublisherInterface          $publisher
    ) {
    }

    /**
     * @inheritDoc
     */
    public function run(): void
    {

        if (!$this->configHelper->isEnabled() || !$this->configHelper->isContactExportEnabled()) {
            return;
        }

        $customerIds = $this->getCustomerIds();

        foreach ($customerIds as $customerId) {
            $this->publisher->publish(
                Topics::CUSTOMER_CONTACT_EXPORT,
                json_encode(['magento_customer_id' => $customerId], JSON_THROW_ON_ERROR)
            );
        }

        $subscriberEmails = $this->getSubscriberEmails();

        foreach ($subscriberEmails as $subscriberEmail) {
            $this->publisher->publish(
                Topics::NEWSLETTER_CONTACT_EXPORT,
                json_encode(['email' => $subscriberEmail], JSON_THROW_ON_ERROR)
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
        $customerCollection->addContactOmittedFilter();

        return $customerCollection->getAllIds();
    }

    /**
     * @return array
     */
    private function getSubscriberEmails(): array
    {
        /** @var SubscriberCollection $subscriberCollection */
        $subscriberCollection = $this->subscriberCollectionFactory->create();
        $subscriberCollection->excludeCustomers();
        $subscriberCollection->addContactOmittedFilter();

        return $subscriberCollection->getAllEmails();
    }
}
