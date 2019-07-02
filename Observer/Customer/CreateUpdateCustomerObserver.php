<?php
declare(strict_types=1);
/**
 */

namespace CommerceLeague\ActiveCampaign\Observer\Customer;

use CommerceLeague\ActiveCampaign\Api\ContactRepositoryInterface;
use CommerceLeague\ActiveCampaign\Api\CustomerRepositoryInterface;
use CommerceLeague\ActiveCampaign\Logger\Logger;
use CommerceLeague\ActiveCampaign\Helper\Config as ConfigHelper;
use CommerceLeague\ActiveCampaign\MessageQueue\Customer\CreateMessageBuilder;
use CommerceLeague\ActiveCampaign\MessageQueue\Topics;
use Magento\Customer\Model\Customer;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\MessageQueue\PublisherInterface;

/**
 * Class CreateUpdateCustomerObserver
 */
class CreateUpdateCustomerObserver implements ObserverInterface
{
    /**
     * @var ConfigHelper
     */
    private $configHelper;

    /**
     * @var ContactRepositoryInterface
     */
    private $customerRepository;

    /**
     * @var Logger
     */
    private $logger;

    /**
     * @var CreateMessageBuilder
     */
    private $createMessageBuilder;

    /**
     * @var PublisherInterface
     */
    private $publisher;

    /**
     * @param ConfigHelper $configHelper
     * @param CustomerRepositoryInterface $customerRepository
     * @param Logger $logger
     * @param CreateMessageBuilder $createMessageBuilder
     * @param PublisherInterface $publisher
     */
    public function __construct(
        ConfigHelper $configHelper,
        CustomerRepositoryInterface $customerRepository,
        Logger $logger,
        CreateMessageBuilder $createMessageBuilder,
        PublisherInterface $publisher
    ) {
        $this->configHelper = $configHelper;
        $this->customerRepository = $customerRepository;
        $this->logger = $logger;
        $this->createMessageBuilder = $createMessageBuilder;
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

        try {
            $customer = $this->customerRepository->getOrCreateByMagentoCustomer($magentoCustomer);
        } catch (CouldNotSaveException $e) {
            $this->logger->critical($e);
            return;
        }

        // TODO::publish update message
        if (!$customer->getActiveCampaignId()) {
            $this->publisher->publish(
                Topics::CUSTOMER_CREATE,
                $this->createMessageBuilder->build($customer, $magentoCustomer)
            );
        }
    }
}
