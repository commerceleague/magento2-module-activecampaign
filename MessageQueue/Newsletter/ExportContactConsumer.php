<?php
declare(strict_types=1);
/**
 */

namespace CommerceLeague\ActiveCampaign\MessageQueue\Newsletter;

use CommerceLeague\ActiveCampaign\Api\ContactRepositoryInterface;
use CommerceLeague\ActiveCampaign\Api\Data\ContactInterface;
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
 *
 * The onXxx() methods below are no-op extension points (not called anywhere
 * else, protected rather than private specifically so an integrator's
 * preference-bound subclass can observe each pipeline stage — e.g. an audit
 * trail — without duplicating this method's control flow). They must never
 * change what consume() itself does; each is called with the same data the
 * matching failure/success recording already computed.
 */
class ExportContactConsumer extends AbstractConsumer implements ConsumerInterface
{

    /**
     * @var SubscriberFactory
     */
    protected $subscriberFactory;

    /**
     * @param SubscriberFactory          $subscriberFactory
     */
    public function __construct(
        SubscriberFactory $subscriberFactory,
        protected readonly ContactRepositoryInterface $contactRepository,
        protected readonly ContactRequestBuilder $contactRequestBuilder,
        protected readonly Client $client,
        protected readonly ManagerInterface $eventManager,
        Logger $logger,
        protected readonly FailureRecorder $failureRecorder,
        protected readonly BackoffState $backoffState
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

        $this->onConsumeStart($message);

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
                    $this->onEmptyResponse($contact);
                    return;
                }

                $contact->setActiveCampaignId($activeCampaignId);
                $this->backoffState->reset();
                $this->failureRecorder->recordSuccess($contact);
                $this->contactRepository->save($contact);
                $this->onSuccess($contact, $activeCampaignId);

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
                $this->onUnprocessable($contact, $e);
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
                $transient = $e->getCode() >= 500 || $e->getCode() === 429;
                $this->failureRecorder->recordFailure($contact, 'http_error', $e->getMessage(), $transient);
                $this->contactRepository->save($contact);
                $this->onHttpError($contact, $e, $transient);
                return;
            }
        } catch (\Throwable $t) {
            $localId = isset($contact) ? $this->castId($contact->getId()) : null;
            $this->logFailure('contact', $localId, null, null, 'unexpected_error', $t->getMessage());
            if (isset($contact)) {
                $this->failureRecorder->recordFailure($contact, 'unexpected_error', $t->getMessage());
                $this->contactRepository->save($contact);
            }
            $this->onUnexpectedError($contact ?? null, $t, null);
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

    /**
     * @param array<mixed> $message the decoded queue message
     */
    protected function onConsumeStart(array $message): void
    {
    }

    protected function onSuccess(ContactInterface $contact, int $activeCampaignId): void
    {
    }

    protected function onEmptyResponse(ContactInterface $contact): void
    {
    }

    protected function onUnprocessable(ContactInterface $contact, UnprocessableEntityHttpException $exception): void
    {
    }

    protected function onHttpError(ContactInterface $contact, HttpException $exception, bool $transient): void
    {
    }

    /**
     * $contact is null when the failure happened before a Contact could be resolved.
     */
    protected function onUnexpectedError(?ContactInterface $contact, \Throwable $exception, mixed $magentoCustomerId): void
    {
    }
}
