<?php
declare(strict_types=1);
/**
 */
namespace CommerceLeague\ActiveCampaign\MessageQueue\Contact;

use CommerceLeague\ActiveCampaign\Api\ContactRepositoryInterface;
use CommerceLeague\ActiveCampaign\Helper\Client as ClientHelper;
use CommerceLeague\ActiveCampaign\Logger\Logger;
use CommerceLeague\ActiveCampaignApi\Exception\HttpException;
use Magento\Framework\Exception\CouldNotSaveException;

/**
 * Class CreateUpdateConsumer
 */
class CreateUpdateConsumer
{
    /**
     * @var ClientHelper
     */
    private $clientHelper;

    /**
     * @var Logger
     */
    private $logger;

    /**
     * @var ContactRepositoryInterface
     */
    private $contactRepository;

    /**
     * @param ClientHelper $clientHelper
     * @param Logger $logger
     * @param ContactRepositoryInterface $contactRepository
     */
    public function __construct(
        ClientHelper $clientHelper,
        Logger $logger,
        ContactRepositoryInterface $contactRepository
    ) {
        $this->logger = $logger;
        $this->contactRepository = $contactRepository;
        $this->clientHelper = $clientHelper;
    }

    /**
     * @param CreateUpdateMessage $message
     */
    public function consume(CreateUpdateMessage $message): void
    {
        $contact = $this->contactRepository->getById($message->getEntityId());

        if (!$contact->getId()) {
            $this->logger->error(__('Unable to find contact with id "%1".', $message->getEntityId()));
            return;
        }

        $request = json_decode($message->getSerializedRequest(), true);

        try {
            $apiResponse = $this->clientHelper->getContactApi()->upsert(['contact' => $request]);
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
