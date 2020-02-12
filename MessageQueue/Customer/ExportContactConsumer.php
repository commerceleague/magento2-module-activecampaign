<?php
declare(strict_types=1);
/**
 */

namespace CommerceLeague\ActiveCampaign\MessageQueue\Customer;

use CommerceLeague\ActiveCampaign\Api\ContactRepositoryInterface;
use CommerceLeague\ActiveCampaign\Api\Data\GuestCustomerInterface;
use CommerceLeague\ActiveCampaign\Gateway\Client;
use CommerceLeague\ActiveCampaign\Gateway\Request\ContactBuilder as ContactRequestBuilder;
use CommerceLeague\ActiveCampaign\Logger\Logger;
use CommerceLeague\ActiveCampaign\MessageQueue\ConsumerInterface;
use CommerceLeague\ActiveCampaignApi\Exception\HttpException;
use CommerceLeague\ActiveCampaignApi\Exception\UnprocessableEntityHttpException;
use Magento\Customer\Api\CustomerRepositoryInterface as MagentoCustomerRepositoryInterface;
use Magento\Framework\Event\ManagerInterface;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;

/**
 * Class ExportContactConsumer
 */
class ExportContactConsumer implements ConsumerInterface
{

    /**
     * @var MagentoCustomerRepositoryInterface
     */
    private $magentoCustomerRepository;

    /**
     * @var Logger
     */
    private $logger;

    /**
     * @var ContactRepositoryInterface
     */
    private $contactRepository;

    /**
     * @var ContactRequestBuilder
     */
    private $contactRequestBuilder;

    /**
     * @var Client
     */
    private $client;

    /**
     * @var ManagerInterface
     */
    private $eventManager;

    /**
     * @param MagentoCustomerRepositoryInterface $magentoCustomerRepository
     * @param Logger                             $logger
     * @param ContactRequestBuilder              $contactRequestBuilder
     * @param ContactRepositoryInterface         $contactRepository
     * @param Client                             $client
     */
    public function __construct(
        MagentoCustomerRepositoryInterface $magentoCustomerRepository,
        Logger $logger,
        ContactRepositoryInterface $contactRepository,
        ContactRequestBuilder $contactRequestBuilder,
        Client $client,
        ManagerInterface $eventManager

    ) {
        $this->magentoCustomerRepository = $magentoCustomerRepository;
        $this->logger                    = $logger;
        $this->contactRepository         = $contactRepository;
        $this->contactRequestBuilder     = $contactRequestBuilder;
        $this->client                    = $client;
        $this->eventManager              = $eventManager;
    }

    /**
     * @param string $message
     *
     * @throws CouldNotSaveException
     */
    public function consume(string $message): void
    {
        $message = json_decode($message, true);

        try {
            $magentoCustomer = $this->magentoCustomerRepository->getById($message['magento_customer_id']);
            $contact         = $this->contactRepository->getOrCreateByEmail($magentoCustomer->getEmail());
            $request         = $this->contactRequestBuilder->buildWithMagentoCustomer($magentoCustomer);
        } catch (NoSuchEntityException|LocalizedException $e) {
            if ($message['customer_is_guest']) {
                // not a customer but a guest
                $guestCustomerData = $message['customer_data'];
                $contact      = $this->contactRepository->getOrCreateByEmail(
                    $guestCustomerData[GuestCustomerInterface::EMAIL]
                );
                $request           = $this->contactRequestBuilder->buildWithGuestContact(
                    $contact,
                    $guestCustomerData[GuestCustomerInterface::FIRSTNAME],
                    $guestCustomerData[GuestCustomerInterface::LASTNAME]
                );
            }
        }

        try {
            $apiResponse = $this->client->getContactApi()->upsert(['contact' => $request]);
        } catch (UnprocessableEntityHttpException $e) {
            $this->logger->error($e->getMessage());
            $this->logger->error(print_r($e->getResponseErrors(), true));
            return;
        } catch (HttpException $e) {
            $this->logger->error($e->getMessage());
            return;
        }

        $contact->setActiveCampaignId($apiResponse['contact']['id']);
        $this->contactRepository->save($contact);
        // trigger event after contact has been saved
        $this->eventManager->dispatch('commmerceleague_activecampaign_export_contact_success', ['contact' => $contact]);
    }
}
