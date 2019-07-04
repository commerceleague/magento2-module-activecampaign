<?php
declare(strict_types=1);
/**
 */
namespace CommerceLeague\ActiveCampaign\MessageQueue;

use CommerceLeague\ActiveCampaign\Api\ContactRepositoryInterface;
use CommerceLeague\ActiveCampaign\Gateway\Client;
use CommerceLeague\ActiveCampaign\Logger\Logger;
use CommerceLeague\ActiveCampaignApi\Exception\HttpException;
use Magento\Framework\Exception\CouldNotSaveException;

/**
 * Class ExportContactConsumer
 */
class ExportContactConsumer
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
     * @var Client
     */
    private $client;

    /**
     * @param ContactRepositoryInterface $contactRepository
     * @param Logger $logger
     * @param Client $client
     */
    public function __construct(
        ContactRepositoryInterface $contactRepository,
        Logger $logger,
        Client $client
    ) {
        $this->contactRepository = $contactRepository;
        $this->logger = $logger;
        $this->client = $client;
    }

    /**
     * @param string $message
     * @throws CouldNotSaveException
     */
    public function consume(string $message): void
    {
        $message = json_decode($message, true);
        $contact = $this->contactRepository->getOrCreateByEmail($message['email']);

        try {
            $apiResponse = $this->client->getContactApi()->upsert(['contact' => $message['request']]);
        } catch (HttpException $e) {
            $this->logger->error($e->getMessage());
            return;
        }

        $contact->setActiveCampaignId($apiResponse['contact']['id']);

        $this->contactRepository->save($contact);
    }
}
