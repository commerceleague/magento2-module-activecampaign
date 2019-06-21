<?php
declare(strict_types=1);
/**
 */
namespace CommerceLeague\ActiveCampaign\Observer\Customer;

use CommerceLeague\ActiveCampaign\Api\ContactRepositoryInterface;
use CommerceLeague\ActiveCampaign\Api\Data\ContactInterface;
use CommerceLeague\ActiveCampaign\Gateway\Request\ContactRequestBuilder;
use CommerceLeague\ActiveCampaign\Helper\Config as ConfigHelper;
use CommerceLeague\ActiveCampaign\Logger\Logger;
use CommerceLeague\ActiveCampaign\MessageQueue\Contact\CreateUpdateMessage;
use CommerceLeague\ActiveCampaign\MessageQueue\Contact\Topics;
use CommerceLeague\ActiveCampaign\Model\Contact;
use Magento\Customer\Model\Customer;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\MessageQueue\PublisherInterface;

/**
 * Class CreateUpdateContactObserver
 */
class CreateUpdateContactObserver implements ObserverInterface
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
     * @var Logger
     */
    private $logger;

    /**
     * @var ContactRequestBuilder
     */
    private $contactRequestBuilder;

    /**
     * @var PublisherInterface
     */
    private $publisher;

    /**
     * @param ConfigHelper $configHelper
     * @param ContactRepositoryInterface $contactRepository
     * @param Logger $logger
     * @param ContactRequestBuilder $contactRequestBuilder
     * @param PublisherInterface $publisher
     */
    public function __construct(
        ConfigHelper $configHelper,
        ContactRepositoryInterface $contactRepository,
        Logger $logger,
        ContactRequestBuilder $contactRequestBuilder,
        PublisherInterface $publisher
    ) {
        $this->configHelper = $configHelper;
        $this->contactRepository = $contactRepository;
        $this->logger = $logger;
        $this->contactRequestBuilder = $contactRequestBuilder;
        $this->publisher = $publisher;
    }

    /**
     * @param Observer $observer
     */
    public function execute(Observer $observer)
    {
        /** @var Customer $customer */
        $customer = $observer->getEvent()->getData('customer');

        if (!$this->configHelper->isApiEnabled()) {
            return;
        }

        try {
            $contactModel = $this->getOrCreateContactModel($customer);
        } catch (CouldNotSaveException $e) {
            $this->logger->critical($e);
            return;
        }

        $this->publisher->publish(
            Topics::CREATE_UPDATE,
            $this->buildMessage($customer, $contactModel)
        );
    }

    /**
     * @param Customer $customer
     * @return ContactInterface
     * @throws CouldNotSaveException
     */
    private function getOrCreateContactModel(Customer $customer): ContactInterface
    {
        /** @var Contact $contact */
        $contact = $this->contactRepository->getByCustomerId($customer->getId());

        if (!$contact->getId()) {
            $contact->setCustomerId($customer->getId());
            $this->contactRepository->save($contact);
        }

        return $contact;
    }

    /**
     * @param Customer $customer
     * @param ContactInterface $contactModel
     * @return CreateUpdateMessage
     */
    private function buildMessage(Customer $customer, ContactInterface $contactModel): CreateUpdateMessage
    {
        $request = $this->contactRequestBuilder->build($customer);
        return CreateUpdateMessage::build((int)$contactModel->getId(), $request);
    }
}
