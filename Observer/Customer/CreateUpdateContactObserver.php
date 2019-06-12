<?php
declare(strict_types=1);
/**
 */
namespace CommerceLeague\ActiveCampaign\Observer\Customer;

use CommerceLeague\ActiveCampaign\Api\ContactRepositoryInterface;
use CommerceLeague\ActiveCampaign\Logger\Logger;
use CommerceLeague\ActiveCampaign\MessageQueue\Contact\CreateUpdatePublisher;
use CommerceLeague\ActiveCampaign\Model\ContactFactory;
use CommerceLeague\ActiveCampaign\Model\Contact;
use Magento\Customer\Model\Customer;
use Magento\Framework\DataObject\Copy as ObjectCopyService;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\NoSuchEntityException;

/**
 * Class RemoveContactObserver
 */
class CreateUpdateContactObserver implements ObserverInterface
{
    /**
     * @var ContactRepositoryInterface
     */
    private $contactRepository;

    /**
     * @var ContactFactory
     */
    private $contactFactory;

    /**
     * @var ObjectCopyService
     */
    protected $objectCopyService;

    /**
     * @var CreateUpdatePublisher
     */
    private $createUpdatePublisher;

    /**
     * @var Logger
     */
    private $logger;

    /**
     * @param ContactRepositoryInterface $contactRepository
     * @param ContactFactory $contactFactory
     * @param ObjectCopyService $objectCopyService
     * @param CreateUpdatePublisher $createUpdatePublisher
     * @param Logger $logger
     */
    public function __construct(
        ContactRepositoryInterface $contactRepository,
        ContactFactory $contactFactory,
        ObjectCopyService $objectCopyService,
        CreateUpdatePublisher $createUpdatePublisher,
        Logger $logger
    ) {
        $this->contactRepository = $contactRepository;
        $this->contactFactory = $contactFactory;
        $this->objectCopyService = $objectCopyService;
        $this->createUpdatePublisher = $createUpdatePublisher;
        $this->logger = $logger;
    }

    /**
     * @inheritDoc
     */
    public function execute(Observer $observer)
    {
        /** @var Customer $customer */
        $customer = $observer->getEvent()->getData('customer');

        try {
            $contact = $this->contactRepository->getByCustomerId($customer->getId());
        } catch (NoSuchEntityException $e) {
            /** @var Contact $contact */
            $contact = $this->contactFactory->create();
        }

        $this->objectCopyService->copyFieldsetToTarget(
            'activecampaign_convert_customer',
            'to_contact',
            $customer,
            $contact
        );

        try {
            $this->contactRepository->save($contact);
            $this->createUpdatePublisher->execute($contact);
        } catch (CouldNotSaveException $e) {
            $this->logger->critical($e);
        }
    }
}
