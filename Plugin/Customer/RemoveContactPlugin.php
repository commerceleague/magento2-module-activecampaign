<?php
declare(strict_types=1);
/**
 */
namespace CommerceLeague\ActiveCampaign\Plugin\Customer;

use CommerceLeague\ActiveCampaign\Api\ContactRepositoryInterface;
use CommerceLeague\ActiveCampaign\Logger\Logger;
use CommerceLeague\ActiveCampaign\MessageQueue\Contact\RemoveMessageFactory;
use CommerceLeague\ActiveCampaign\MessageQueue\Contact\RemoveMessage;
use CommerceLeague\ActiveCampaign\MessageQueue\Contact\RemovePublisher;
use Magento\Customer\Model\Customer;
use Magento\Customer\Model\ResourceModel\Customer as Subject;
use Magento\Framework\Exception\NoSuchEntityException;

/**
 * Class RemoveContactPlugin
 */
class RemoveContactPlugin
{
    /**
     * @var ContactRepositoryInterface
     */
    private $contactRepository;

    /**
     * @var RemoveMessageFactory
     */
    private $removeMessageFactory;

    /**
     * @var RemovePublisher
     */
    private $removePublisher;

    /**
     * @var Logger
     */
    private $logger;

    /**
     * @param ContactRepositoryInterface $contactRepository
     * @param RemoveMessageFactory $removeMessageFactory
     * @param RemovePublisher $removePublisher
     * @param Logger $logger
     */
    public function __construct(
        ContactRepositoryInterface $contactRepository,
        RemoveMessageFactory $removeMessageFactory,
        RemovePublisher $removePublisher,
        Logger $logger
    ) {
        $this->contactRepository = $contactRepository;
        $this->removeMessageFactory = $removeMessageFactory;
        $this->removePublisher = $removePublisher;
        $this->logger = $logger;
    }

    /**
     * @param Subject $subject
     * @param callable $proceed
     * @param Customer $object
     * @return Subject
     */
    public function aroundDelete(Subject $subject, callable $proceed, $object)
    {
        try {
            $contact = $this->contactRepository->getByCustomerId($object->getId());
        } catch (NoSuchEntityException $e) {
            $this->logger->critical($e);
        }

        $proceed($object);

        if (isset($contact) && $contact->getActiveCampaignId() !== null) {
            /** @var RemoveMessage $message */
            $message = $this->removeMessageFactory->create();
            $message->setActiveCampaignId($contact->getActiveCampaignId());

            $this->removePublisher->execute($message);
        }

        return $subject;
    }
}
