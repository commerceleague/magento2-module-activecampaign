<?php
declare(strict_types=1);
/**
 */
namespace CommerceLeague\ActiveCampaign\Observer\Customer;

use CommerceLeague\ActiveCampaign\Api\ContactRepositoryInterface;
use CommerceLeague\ActiveCampaign\Helper\Config as ConfigHelper;
use CommerceLeague\ActiveCampaign\Logger\Logger;
use CommerceLeague\ActiveCampaign\MessageQueue\Contact\CreateUpdateMessageBuilder;
use CommerceLeague\ActiveCampaign\MessageQueue\Topics;
use Magento\Framework\MessageQueue\PublisherInterface;
use Magento\Customer\Model\Customer;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Exception\CouldNotSaveException;

/**
 * Class SyncContactObserver
 */
class SyncContactObserver implements ObserverInterface
{
    /**
     * @var ConfigHelper
     */
    private $configHelper;

    /**
     * @var ContactRepositoryInterface
     */
    private $contactRepository;

    /**
     * @var CreateUpdateMessageBuilder
     */
    private $createUpdateMessageBuilder;

    /**
     * @var Logger
     */
    private $logger;

    /**
     * @var PublisherInterface
     */
    private $publisher;

    /**
     * @param ConfigHelper $configHelper
     * @param ContactRepositoryInterface $contactRepository
     * @param Logger $logger
     * @param CreateUpdateMessageBuilder $createUpdateMessageBuilder
     * @param PublisherInterface $publisher
     */
    public function __construct(
        ConfigHelper $configHelper,
        ContactRepositoryInterface $contactRepository,
        Logger $logger,
        CreateUpdateMessageBuilder $createUpdateMessageBuilder,
        PublisherInterface $publisher
    ) {
        $this->configHelper = $configHelper;
        $this->contactRepository = $contactRepository;
        $this->logger = $logger;
        $this->createUpdateMessageBuilder = $createUpdateMessageBuilder;
        $this->publisher = $publisher;
    }

    /**
     * @param Observer $observer
     */
    public function execute(Observer $observer)
    {
        if (!$this->configHelper->isApiEnabled()) {
            return;
        }

        /** @var Customer $customer */
        $customer = $observer->getEvent()->getData('customer');

        try {
            $contact = $this->contactRepository->getOrCreateByCustomer($customer);
        } catch (CouldNotSaveException $e) {
            $this->logger->critical($e);
            return;
        }

        $this->publisher->publish(
            Topics::CONTACT_CREATE_UPDATE,
            $this->createUpdateMessageBuilder->buildWithCustomer($contact, $customer)
        );
    }
}
