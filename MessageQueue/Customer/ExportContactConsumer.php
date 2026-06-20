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
use CommerceLeague\ActiveCampaign\MessageQueue\AbstractConsumer;
use CommerceLeague\ActiveCampaign\MessageQueue\ConsumerInterface;
use CommerceLeague\ActiveCampaign\Model\Export\BackoffState;
use CommerceLeague\ActiveCampaign\Model\Export\FailureRecorder;
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
class ExportContactConsumer extends AbstractConsumer implements ConsumerInterface
{

    public function __construct(
        private readonly MagentoCustomerRepositoryInterface $magentoCustomerRepository,
        Logger $logger,
        private readonly ContactRepositoryInterface $contactRepository,
        private readonly ContactRequestBuilder $contactRequestBuilder,
        private readonly Client $client,
        private readonly ManagerInterface $eventManager,
        private readonly FailureRecorder $failureRecorder,
        private readonly BackoffState $backoffState
    ) {
        parent::__construct($logger);
    }

    /**
     * @throws CouldNotSaveException
     */
    public function consume(string $message): void
    {
        $message = json_decode($message, true, 512, JSON_THROW_ON_ERROR);

        if ($this->backoffState->shouldHalt()) {
            $this->getLogger()->warning('ActiveCampaign export backing off after repeated 503s; skipping');
            return;
        }

        try {
            $magentoCustomer = $this->magentoCustomerRepository->getById($message['magento_customer_id']);
            $contact         = $this->contactRepository->getOrCreateByEmail($magentoCustomer->getEmail());
            $request         = $this->contactRequestBuilder->buildWithMagentoCustomer($magentoCustomer);

        } catch (NoSuchEntityException|LocalizedException $e) {
            if (array_key_exists('customer_is_guest', $message)) {
                // not a customer but a guest
                $guestCustomerData = $message['customer_data'];
                $contact           = $this->contactRepository->getOrCreateByEmail(
                    $guestCustomerData[GuestCustomerInterface::EMAIL]
                );
                $request           = $this->contactRequestBuilder->buildWithGuestContact(
                    $contact,
                    $guestCustomerData[GuestCustomerInterface::FIRSTNAME],
                    $guestCustomerData[GuestCustomerInterface::LASTNAME]
                );
            } else {
                $this->getLogger()->error($e->getMessage());
                return;
            }
        }

        try {
            $apiResponse = $this->client->getContactApi()->upsert(['contact' => $request]);

            $activeCampaignId = $this->extractActiveCampaignId($apiResponse[self::RESPONSE_KEY_CONTACT]['id'] ?? null);
            if ($activeCampaignId === null) {
                $this->getLogger()->error(sprintf(
                    '%s: missing "%s.id" in API response for contact id "%s"; skipping save.',
                    static::class,
                    self::RESPONSE_KEY_CONTACT,
                    (string)$contact->getId()
                ));
                $this->failureRecorder->recordFailure($contact, 'empty_response', null);
                $this->contactRepository->save($contact);
                return;
            }

            $contact->setActiveCampaignId($activeCampaignId);
            $this->backoffState->reset();
            $this->failureRecorder->recordSuccess($contact);
            $this->contactRepository->save($contact);
            // trigger event after contact has been saved
            $this->eventManager->dispatch('commmerceleague_activecampaign_export_contact_success', ['contact' => $contact]);
        } catch (UnprocessableEntityHttpException $e) {
            $this->logUnprocessableEntityHttpException($e, $request);
            $this->failureRecorder->recordFailure($contact, 'unknown', $e->getMessage());
            $this->contactRepository->save($contact);
            return;
        } catch (HttpException $e) {
            if ($e->getCode() === 503) {
                $this->backoffState->record503();
            }
            $this->logException($e);
            $transient = $e->getCode() >= 500;
            $this->failureRecorder->recordFailure($contact, 'http_error', $e->getMessage(), $transient);
            $this->contactRepository->save($contact);
            return;
        }

    }

    /**
     * @inheritDoc
     */
    function processDuplicateEntity(array $request, string $key): array
    {
        return [];
    }
}
