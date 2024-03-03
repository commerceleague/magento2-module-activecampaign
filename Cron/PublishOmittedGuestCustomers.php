<?php
declare(strict_types=1);
/**
 */

namespace CommerceLeague\ActiveCampaign\Cron;

use CommerceLeague\ActiveCampaign\Api\CronInterface;
use CommerceLeague\ActiveCampaign\Api\Data\GuestCustomerInterface;
use CommerceLeague\ActiveCampaign\Helper\Config as ConfigHelper;
use CommerceLeague\ActiveCampaign\MessageQueue\Topics;
use CommerceLeague\ActiveCampaign\Model\ResourceModel\ActiveCampaign\GuestCustomer\CollectionFactory as CustomerCollectionFactory;
use CommerceLeague\ActiveCampaign\vendor\Model\ResourceModel\ActiveCampaign\GuestCustomer\Collection as CustomerCollection;
use Magento\Framework\MessageQueue\PublisherInterface;

/**
 * Class PublishOmittedGuestCustomers
 */
class PublishOmittedGuestCustomers implements CronInterface
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

        $guestCustomers = $this->getCustomers();

        /** @var GuestCustomerInterface $customer */
        foreach ($guestCustomers as $customer) {
            $this->publisher->publish(
                Topics::GUEST_CUSTOMER_EXPORT,
                json_encode(
                    [
                        'magento_customer_id' => null,
                        'customer_is_guest'   => true,
                        'customer_data'       => [
                            GuestCustomerInterface::FIRSTNAME => $customer->getFirstname(),
                            GuestCustomerInterface::LASTNAME  => $customer->getLastname(),
                            GuestCustomerInterface::EMAIL     => $customer->getEmail()
                        ]
                    ]
                )
            );
        }
    }

    /**
     * @return array
     */
    private function getCustomers(): array
    {
        /** @var CustomerCollection $customerCollection */
        $customerCollection = $this->customerCollectionFactory->create();
        $customerCollection->addOmittedFilter();
        $customerCollection->addExportFilterOrderStatus();
        $customerCollection->addExportFilterStartDate();

        return $customerCollection->getItems();
    }
}
