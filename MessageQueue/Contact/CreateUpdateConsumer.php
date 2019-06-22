<?php
declare(strict_types=1);
/**
 */
namespace CommerceLeague\ActiveCampaign\MessageQueue\Contact;

use CommerceLeague\ActiveCampaign\Api\ContactRepositoryInterface;
use CommerceLeague\ActiveCampaign\Gateway\Client;
use CommerceLeague\ActiveCampaign\Logger\Logger;
use CommerceLeague\ActiveCampaignApi\Exception\HttpException;
use Magento\Framework\Exception\CouldNotSaveException;

/**
 * Class CreateUpdateConsumer
 */
class CreateUpdateConsumer
{
    /**
     * @var Client
     */
    private $client;

    /**
     * @var Logger
     */
    private $logger;

    /**
     * @var ContactRepositoryInterface
     */
    private $contactRepository;

    /**
     * @param Client $client
     * @param Logger $logger
     * @param ContactRepositoryInterface $contactRepository
     */
    public function __construct(
        Client $client,
        Logger $logger,
        ContactRepositoryInterface $contactRepository
    ) {
        $this->logger = $logger;
        $this->contactRepository = $contactRepository;
        $this->client = $client;
    }

    /**
     * @param CreateUpdateMessage $message
     */
    public function consume(CreateUpdateMessage $message): void
    {
        $contact = $this->contactRepository->getById($message->getContactId());

        if (!$contact->getId()) {
            $this->logger->error(__('Unable to find contact with id "%1".', $message->getContactId()));
            return;
        }

        $request = json_decode($message->getSerializedRequest(), true);

        try {
            $apiResponse = $this->client->getContactApi()->upsert(['contact' => $request]);
        } catch (HttpException $e) {
            $this->logger->error($e->getMessage());
            return;
        }

        $contact->setActiveCampaignId($apiResponse['contact']['id']);

        try {
            $this->contactRepository->save($contact);
        } catch (CouldNotSaveException $e) {
            $this->logger->error($e->getMessage());
        }
    }
}
