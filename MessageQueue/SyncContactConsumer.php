<?php
declare(strict_types=1);
/**
 */
namespace CommerceLeague\ActiveCampaign\MessageQueue;

use CommerceLeague\ActiveCampaign\Api\ContactRepositoryInterface;
use CommerceLeague\ActiveCampaign\Helper\Client as ClientHelper;
use CommerceLeague\ActiveCampaign\Logger\Logger;
use CommerceLeague\ActiveCampaignApi\Exception\HttpException;
use Magento\Framework\Exception\CouldNotSaveException;

/**
 * Class CreateUpdateConsumer
 */
class SyncContactConsumer
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
     * @var ClientHelper
     */
    private $clientHelper;

    /**
     * @param ContactRepositoryInterface $contactRepository
     * @param Logger $logger
     * @param ClientHelper $clientHelper
     */
    public function __construct(
        ContactRepositoryInterface $contactRepository,
        Logger $logger,
        ClientHelper $clientHelper
    ) {
        $this->contactRepository = $contactRepository;
        $this->logger = $logger;
        $this->clientHelper = $clientHelper;
    }

    /**
     * @param string $message
     */
    public function consume(string $message): void
    {
        $message = json_decode($message, true);

        try {
            $contact = $this->contactRepository->getOrCreateByEmail($message['email']);
        } catch (CouldNotSaveException $e) {
            $this->logger->error(__('Unable to find contact with email "%1".', $message['email']));
            return;
        }

        try {
            $apiResponse = $this->clientHelper->getContactApi()->upsert(['contact' => $message['request']]);
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
