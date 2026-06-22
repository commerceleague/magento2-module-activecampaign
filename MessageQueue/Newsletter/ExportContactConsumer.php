<?php
declare(strict_types=1);
/**
 */

namespace CommerceLeague\ActiveCampaign\MessageQueue\Newsletter;

use CommerceLeague\ActiveCampaign\Api\ContactRepositoryInterface;
use CommerceLeague\ActiveCampaign\Gateway\Client;
use CommerceLeague\ActiveCampaign\Gateway\Request\ContactBuilder as ContactRequestBuilder;
use CommerceLeague\ActiveCampaign\Logger\Logger;
use CommerceLeague\ActiveCampaign\MessageQueue\AbstractConsumer;
use CommerceLeague\ActiveCampaign\MessageQueue\ConsumerInterface;
use CommerceLeague\ActiveCampaign\Model\Export\BackoffState;
use CommerceLeague\ActiveCampaign\Model\Export\FailureRecorder;
use CommerceLeague\ActiveCampaignApi\Exception\HttpException;
use CommerceLeague\ActiveCampaignApi\Exception\UnprocessableEntityHttpException;
use Magento\Framework\Event\ManagerInterface;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Newsletter\Model\SubscriberFactory;
use Magento\Newsletter\Model\Subscriber;

/**
 * Class ExportContactConsumer
 */
class ExportContactConsumer extends AbstractConsumer implements ConsumerInterface
{

    /**
     * @var SubscriberFactory
     */
    private $subscriberFactory;

    /**
     * @param SubscriberFactory          $subscriberFactory
     */
    public function __construct(
        SubscriberFactory $subscriberFactory,
        private readonly ContactRepositoryInterface $contactRepository,
        private readonly ContactRequestBuilder $contactRequestBuilder,
        private readonly Client $client,
        private readonly ManagerInterface $eventManager,
        Logger $logger,
        private readonly FailureRecorder $failureRecorder,
        private readonly BackoffState $backoffState
    ) {
        parent::__construct($logger);
        $this->subscriberFactory     = $subscriberFactory;
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

        /** @var Subscriber $subscriber */
        $subscriber = $this->subscriberFactory->create();
        $subscriber = $subscriber->load($message['email'], 'subscriber_email');

        if ($subscriber->getId() === 0) {
            $this->getLogger()->error(__('The Subscriber with the "%1" email doesn\'t exist', $message['email']));
            return;
        }

        try {
            $contact = $this->contactRepository->getOrCreateByEmail($subscriber->getEmail());
            $request = $this->contactRequestBuilder->buildWithSubscriber($subscriber);

            try {
                $apiResponse = $this->client->getContactApi()->upsert(['contact' => $request]);

                $activeCampaignId = $this->extractActiveCampaignId(
                    $apiResponse[self::RESPONSE_KEY_CONTACT]['id'] ?? null
                );
                if ($activeCampaignId === null) {
                    $this->logFailure(
                        'contact',
                        $this->castId($contact->getId()),
                        null,
                        null,
                        'empty_response',
                        sprintf('missing "%s.id" in API response; skipping save', self::RESPONSE_KEY_CONTACT)
                    );
                    $this->failureRecorder->recordFailure($contact, 'empty_response', null);
                    $this->contactRepository->save($contact);
                    return;
                }

                $contact->setActiveCampaignId($activeCampaignId);
                $this->backoffState->reset();
                $this->failureRecorder->recordSuccess($contact);
                $this->contactRepository->save($contact);

                // trigger event after contact has been saved
                $this->eventManager->dispatch(
                    'commmerceleague_activecampaign_export_newsletter_subscriber_success',
                    ['contact' => $contact]
                );
            } catch (UnprocessableEntityHttpException $e) {
                $this->logFailure(
                    'contact',
                    $this->castId($contact->getId()),
                    null,
                    $e->getCode() ?: 422,
                    'unknown',
                    $e->getMessage()
                );
                $this->failureRecorder->recordFailure($contact, 'unknown', $e->getMessage());
                $this->contactRepository->save($contact);
                return;
            } catch (HttpException $e) {
                if ($e->getCode() === 503) {
                    $this->backoffState->record503();
                }
                $this->logFailure(
                    'contact',
                    $this->castId($contact->getId()),
                    null,
                    $e->getCode(),
                    'http_error',
                    $e->getMessage()
                );
                $transient = $e->getCode() >= 500;
                $this->failureRecorder->recordFailure($contact, 'http_error', $e->getMessage(), $transient);
                $this->contactRepository->save($contact);
                return;
            }
        } catch (\Throwable $t) {
            $localId = isset($contact) ? $this->castId($contact->getId()) : null;
            $this->logFailure('contact', $localId, null, null, 'unexpected_error', $t->getMessage());
            if (isset($contact)) {
                $this->failureRecorder->recordFailure($contact, 'unexpected_error', $t->getMessage());
                $this->contactRepository->save($contact);
            }
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
