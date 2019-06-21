<?php
declare(strict_types=1);
/**
 */
namespace CommerceLeague\ActiveCampaign\Observer\Customer;

use CommerceLeague\ActiveCampaign\Api\ContactRepositoryInterface;
use CommerceLeague\ActiveCampaign\Api\Data\ContactInterface;
use CommerceLeague\ActiveCampaign\Helper\Config as ConfigHelper;
use CommerceLeague\ActiveCampaign\Logger\Logger;
use CommerceLeague\ActiveCampaign\MessageQueue\Contact\CreateUpdateMessageFactory;
use CommerceLeague\ActiveCampaign\MessageQueue\Contact\CreateUpdateMessage;
use CommerceLeague\ActiveCampaign\MessageQueue\Contact\Topics;
use CommerceLeague\ActiveCampaign\Model\Contact;
use Magento\Customer\Model\Customer;
use Magento\Framework\DataObject\Copy as DataObjectCopy;
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
     * @var DataObjectCopy
     */
    private $dataObjectCopy;

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
     * @param DataObjectCopy $dataObjectCopy
     * @param Logger $logger
     * @param PublisherInterface $publisher
     */
    public function __construct(
        ConfigHelper $configHelper,
        ContactRepositoryInterface $contactRepository,
        DataObjectCopy $dataObjectCopy,
        Logger $logger,
        PublisherInterface $publisher
    ) {
        $this->configHelper = $configHelper;
        $this->contactRepository = $contactRepository;
        $this->dataObjectCopy = $dataObjectCopy;
        $this->logger = $logger;
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
            $this->dataObjectCopy->copyFieldsetToTarget(
                'activecampaign_convert_customer',
                'to_contact',
                $customer,
                $contact
            );

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
        $request = $this->dataObjectCopy->getDataFromFieldset(
            'activecampaign_convert_customer',
            'to_api_request',
            $customer
        );

        return CreateUpdateMessage::build(
            (int)$contactModel->getId(),
            json_encode($request)
        );
    }
}
