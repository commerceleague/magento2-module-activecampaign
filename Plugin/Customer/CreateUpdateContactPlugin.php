<?php
declare(strict_types=1);
/**
 */
namespace CommerceLeague\ActiveCampaign\Plugin\Customer;

use CommerceLeague\ActiveCampaign\Api\ContactRepositoryInterface;
use CommerceLeague\ActiveCampaign\Logger\Logger;
use CommerceLeague\ActiveCampaign\MessageQueue\Contact\CreateUpdateMessageFactory;
use CommerceLeague\ActiveCampaign\MessageQueue\Contact\CreateUpdateMessage;
use CommerceLeague\ActiveCampaign\MessageQueue\Contact\CreateUpdatePublisher;
use CommerceLeague\ActiveCampaign\Model\Contact;
use CommerceLeague\ActiveCampaign\Model\ContactFactory;
use Magento\Customer\Model\Customer;
use Magento\Customer\Model\ResourceModel\Customer as Subject;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Model\AbstractModel;

/**
 * Class CreateUpdateContactPlugin
 */
class CreateUpdateContactPlugin
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
     * @var CreateUpdateMessageFactory
     */
    private $createUpdateMessageFactory;

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
     * @param CreateUpdateMessageFactory $createUpdateMessageFactory
     * @param CreateUpdatePublisher $createUpdatePublisher
     * @param Logger $logger
     */
    public function __construct(
        ContactRepositoryInterface $contactRepository,
        ContactFactory $contactFactory,
        CreateUpdateMessageFactory $createUpdateMessageFactory,
        CreateUpdatePublisher $createUpdatePublisher,
        Logger $logger
    ) {
        $this->contactRepository = $contactRepository;
        $this->contactFactory = $contactFactory;
        $this->createUpdateMessageFactory = $createUpdateMessageFactory;
        $this->createUpdatePublisher = $createUpdatePublisher;
        $this->logger = $logger;
    }

    /**
     * @param Subject $subject
     * @param callable $proceed
     * @param AbstractModel|Customer $object
     * @return Subject
     */
    public function aroundSave(Subject $subject, callable $proceed, AbstractModel $object)
    {
        $proceed($object);

        try {
            $contact = $this->contactRepository->getByCustomerId($object->getId());
        } catch (NoSuchEntityException $e) {
            /** @var Contact $contact */
            $contact = $this->contactFactory->create();
        }

        $contact
            ->setCustomerId($object->getId())
            ->setEmail($object->getEmail());

        /** @var CreateUpdateMessage $message */
        $message = $this->createUpdateMessageFactory->create();
        $message->setCustomerId($object->getId());

        try {
            $this->contactRepository->save($contact);
            $this->createUpdatePublisher->execute($message);
        } catch (CouldNotSaveException $e) {
            $this->logger->critical($e);
        }

        return $subject;
    }
}
