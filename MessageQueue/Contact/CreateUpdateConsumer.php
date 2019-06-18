<?php
declare(strict_types=1);
/**
 */
namespace CommerceLeague\ActiveCampaign\MessageQueue\Contact;

use CommerceLeague\ActiveCampaign\Api\ContactRepositoryInterface;
use CommerceLeague\ActiveCampaign\Gateway\Endpoint\ContactEndpoint;
use CommerceLeague\ActiveCampaign\Gateway\GatewayException;
use CommerceLeague\ActiveCampaign\Logger\Logger;
use Magento\Framework\Exception\CouldNotSaveException;

/**
 * Class CreateUpdateConsumer
 */
class CreateUpdateConsumer
{
    /**
     * @var ContactEndpoint
     */
    private $contactEndpoint;

    /**
     * @var Logger
     */
    private $logger;

    /**
     * @var ContactRepositoryInterface
     */
    private $contactRepository;

    /**
     * @param ContactEndpoint $contactEndpoint
     * @param Logger $logger
     * @param ContactRepositoryInterface $contactRepository
     */
    public function __construct(
        ContactEndpoint $contactEndpoint,
        Logger $logger,
        ContactRepositoryInterface $contactRepository
    ) {
        $this->contactEndpoint = $contactEndpoint;
        $this->logger = $logger;
        $this->contactRepository = $contactRepository;
    }

    /**
     * @param CreateUpdateMessage $message
     * @throws \Zend_Http_Client_Exception
     */
    public function consume(CreateUpdateMessage $message): void
    {
        $request = json_decode($message->getSerializedRequest(), true);

        try {
            $activeCampaignId = $this->contactEndpoint->sync($request);
        } catch (GatewayException $e) {
            $this->logger->error($e->getMessage());
            return;
        }

        $contact = $this->contactRepository->getById($message->getContactId());

        if (!$contact->getId()) {
            $this->logger->error(__('Unable to find contact with id "%s".', $message->getContactId()));
            return;
        }

        $contact->setActiveCampaignId($activeCampaignId);

        try {
            $this->contactRepository->save($contact);
        } catch (CouldNotSaveException $e) {
            $this->logger->error($e->getMessage());
        }
    }
}
