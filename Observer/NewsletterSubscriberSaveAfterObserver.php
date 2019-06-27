<?php
declare(strict_types=1);
/**
 */

namespace CommerceLeague\ActiveCampaign\Observer;

use CommerceLeague\ActiveCampaign\Api\ContactRepositoryInterface;
use CommerceLeague\ActiveCampaign\Logger\Logger;
use CommerceLeague\ActiveCampaign\MessageQueue\Contact\CreateUpdateMessageBuilder;
use CommerceLeague\ActiveCampaign\MessageQueue\Contact\CreateUpdatePublisher;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Newsletter\Model\Subscriber;
use CommerceLeague\ActiveCampaign\Helper\Config as ConfigHelper;

/**
 * Class NewsletterSubscriberSaveAfterObserver
 */
class NewsletterSubscriberSaveAfterObserver implements ObserverInterface
{
    /**
     * @var ConfigHelper
     */
    private $configHelper;

    /**
     * @var ContactRepositoryInterface
     */
    private $contactRepository;

    /**
     * @var Logger
     */
    private $logger;

    /**
     * @var CreateUpdateMessageBuilder
     */
    private $createUpdateMessageBuilder;

    /**
     * @var CreateUpdatePublisher
     */
    private $createUpdatePublisher;

    /**
     * @param ConfigHelper $configHelper
     * @param ContactRepositoryInterface $contactRepository
     * @param Logger $logger
     * @param CreateUpdateMessageBuilder $createUpdateMessageBuilder
     * @param CreateUpdatePublisher $createUpdatePublisher
     */
    public function __construct(
        ConfigHelper $configHelper,
        ContactRepositoryInterface $contactRepository,
        Logger $logger,
        CreateUpdateMessageBuilder $createUpdateMessageBuilder,
        CreateUpdatePublisher $createUpdatePublisher
    ) {
        $this->configHelper = $configHelper;
        $this->contactRepository = $contactRepository;
        $this->logger = $logger;
        $this->createUpdateMessageBuilder = $createUpdateMessageBuilder;
        $this->createUpdatePublisher = $createUpdatePublisher;
    }

    /**
     * @inheritDoc
     */
    public function execute(Observer $observer)
    {
        if (!$this->configHelper->isApiEnabled()) {
            return;
        }

        /** @var Subscriber $subscriber */
        $subscriber = $observer->getEvent()->getData('subscriber');

        try {
            $contact = $this->contactRepository->getOrCreateBySubscriber($subscriber);
        } catch (CouldNotSaveException $e) {
            $this->logger->critical($e);
            return;
        }

        $this->createUpdatePublisher->publish(
            $this->createUpdateMessageBuilder->buildWithSubscriber($contact, $subscriber)
        );
    }
}
