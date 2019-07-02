<?php
declare(strict_types=1);
/**
 */

namespace CommerceLeague\ActiveCampaign\MessageQueue\Contact;

use CommerceLeague\ActiveCampaign\Api\Data\ContactInterface;
use CommerceLeague\ActiveCampaign\Helper\Client as ClientHelper;
use CommerceLeague\ActiveCampaign\Logger\Logger;
use CommerceLeague\ActiveCampaign\MessageQueue\Topics;
use CommerceLeague\ActiveCampaign\Model\ResourceModel\Contact as ContactResource;
use CommerceLeague\ActiveCampaignApi\Exception\HttpException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\MessageQueue\PublisherInterface;

/**
 * Class ImportConsumer
 */
class ImportConsumer
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
     * @var ContactResource
     */
    private $contactResource;

    /**
     * @var ImportMessageBuilder
     */
    private $importMessageBuilder;

    /**
     * @var PublisherInterface
     */
    private $publisher;

    /**
     * @param ClientHelper $clientHelper
     * @param Logger $logger
     * @param ContactResource $contactResource
     * @param ImportMessageBuilder $importMessageBuilder
     * @param PublisherInterface $publisher
     */
    public function __construct(
        ClientHelper $clientHelper,
        Logger $logger,
        ContactResource $contactResource,
        ImportMessageBuilder $importMessageBuilder,
        PublisherInterface $publisher
    ) {
        $this->clientHelper = $clientHelper;
        $this->logger = $logger;
        $this->contactResource = $contactResource;
        $this->importMessageBuilder = $importMessageBuilder;
        $this->publisher = $publisher;
    }

    /**
     * @param ImportMessage $message
     */
    public function consume(ImportMessage $message): void
    {
        try {
            $page = $this->clientHelper->getContactApi()->listPerPage($message->getLimit(), $message->getOffset());
        } catch (HttpException $e) {
            $this->logger->err($e->getMessage());
            return;
        }

        $contacts = array_map(function (array $item) {
            return [
                ContactInterface::EMAIL => $item['email'],
                ContactInterface::ACTIVE_CAMPAIGN_ID => $item['id']
            ];
        }, $page->getItems());

        try {
            $this->contactResource->importContacts($contacts);
        } catch (LocalizedException $e) {
            $this->logger->err($e->getMessage());
            return;
        }

        if (!$page->hasNextPage()) {
            return;
        }

        $this->publisher->publish(
            Topics::CONTACT_IMPORT,
            $this->importMessageBuilder->buildNextMessage($message)
        );
    }
}
