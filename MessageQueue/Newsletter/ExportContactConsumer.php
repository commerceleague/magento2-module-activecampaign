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
     * @param SubscriberFactory          $subscriberFactory
     * @param Logger                     $logger
     * @param ContactRepositoryInterface $contactRepository
     * @param ContactRequestBuilder      $contactRequestBuilder
     * @param Client                     $client
     * @param ManagerInterface           $eventManager
     */
    public function __construct(
        SubscriberFactory $subscriberFactory,
        ContactRepositoryInterface $contactRepository,
        ContactRequestBuilder $contactRequestBuilder,
        Client $client,
        ManagerInterface $eventManager,
        Logger $logger
    ) {
        parent::__construct($logger);
        $this->subscriberFactory     = $subscriberFactory;
        $this->contactRepository     = $contactRepository;
        $this->contactRequestBuilder = $contactRequestBuilder;
        $this->client                = $client;
        $this->eventManager          = $eventManager;
    }

    /**
     * @param string $message
     *
     * @throws CouldNotSaveException
     */
    public function consume(string $message): void
    {
        $message = json_decode($message, true);

        /** @var Subscriber $subscriber */
        $subscriber = $this->subscriberFactory->create();
        $subscriber = $subscriber->loadByEmail($message['email']);

        if (!$subscriber->getId()) {
            $this->getLogger()->error(__('The Subscriber with the "%1" email doesn\'t exist', $message['email']));
            return;
        }

        $contact = $this->contactRepository->getOrCreateByEmail($subscriber->getEmail());
        $request = $this->contactRequestBuilder->buildWithSubscriber($subscriber);

        try {
            $apiResponse = $this->client->getContactApi()->upsert(['contact' => $request]);
        } catch (UnprocessableEntityHttpException $e) {
            $this->logUnprocessableEntityHttpException($e, $request);
            return;
        } catch (HttpException $e) {
            $this->logException($e);
            return;
        }

        $contact->setActiveCampaignId($apiResponse['contact']['id']);
        $this->contactRepository->save($contact);

        // trigger event after contact has been saved
        $this->eventManager->dispatch(
            'commmerceleague_activecampaign_export_newsletter_subscriber_success',
            ['contact' => $contact]
        );
    }

    /**
     * @inheritDoc
     */
    function processDuplicateEntity(array $request, string $key)
    {
        return;
    }
}
