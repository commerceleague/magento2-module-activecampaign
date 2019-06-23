<?php
declare(strict_types=1);
/**
 */

namespace CommerceLeague\ActiveCampaign\Service\Contact;

use CommerceLeague\ActiveCampaign\Api\ContactRepositoryInterface;
use CommerceLeague\ActiveCampaign\Gateway\Request\ContactRequestBuilder;
use CommerceLeague\ActiveCampaign\Logger\Logger;
use CommerceLeague\ActiveCampaign\MessageQueue\Contact\CreateUpdateMessage;
use CommerceLeague\ActiveCampaign\MessageQueue\Contact\CreateUpdatePublisher;
use Magento\Customer\Model\Customer;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Newsletter\Model\Subscriber;

/**
 * Class CreateUpdateContactService
 */
class CreateUpdateContactService
{
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
     * @var CreateUpdatePublisher
     */
    private $createUpdatePublisher;

    /**
     * @param ContactRepositoryInterface $contactRepository
     * @param Logger $logger
     * @param ContactRequestBuilder $contactRequestBuilder
     * @param CreateUpdatePublisher $createUpdatePublisher
     */
    public function __construct(
        ContactRepositoryInterface $contactRepository,
        Logger $logger,
        ContactRequestBuilder $contactRequestBuilder,
        CreateUpdatePublisher $createUpdatePublisher
    ) {
        $this->contactRepository = $contactRepository;
        $this->logger = $logger;
        $this->contactRequestBuilder = $contactRequestBuilder;
        $this->createUpdatePublisher = $createUpdatePublisher;
    }

    /**
     * @param Customer $customer
     */
    public function executeWithCustomer(Customer $customer): void
    {
        try {
            $contact = $this->contactRepository->getOrCreateByCustomer($customer);
        } catch (CouldNotSaveException $e) {
            $this->logger->critical($e);
            return;
        }

        $message = CreateUpdateMessage::build(
            (int)$contact->getId(),
            $this->contactRequestBuilder->buildWithCustomer($customer)
        );

        $this->createUpdatePublisher->publish($message);
    }

    /**
     * @param Subscriber $subscriber
     */
    public function executeWithSubscriber(Subscriber $subscriber): void
    {
        try {
            $contact = $this->contactRepository->getOrCreateBySubscriber($subscriber);
        } catch (CouldNotSaveException $e) {
            $this->logger->critical($e);
            return;
        }

        $message = CreateUpdateMessage::build(
            (int)$contact->getId(),
            $this->contactRequestBuilder->buildWithSubscriber($subscriber)
        );

        $this->createUpdatePublisher->publish($message);
    }
}
