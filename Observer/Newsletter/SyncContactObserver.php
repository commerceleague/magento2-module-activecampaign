<?php
declare(strict_types=1);
/**
 */

namespace CommerceLeague\ActiveCampaign\Observer\Newsletter;

use CommerceLeague\ActiveCampaign\Api\ContactRepositoryInterface;
use CommerceLeague\ActiveCampaign\Logger\Logger;
use CommerceLeague\ActiveCampaign\MessageQueue\Contact\CreateUpdateMessageBuilder;
use CommerceLeague\ActiveCampaign\MessageQueue\Topics;
use Magento\Framework\MessageQueue\PublisherInterface;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Newsletter\Model\Subscriber;
use CommerceLeague\ActiveCampaign\Helper\Config as ConfigHelper;

/**
 * Class SyncContactObserver
 */
class SyncContactObserver implements ObserverInterface
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
     * @var PublisherInterface
     */
    private $publisher;

    /**
     * @param ConfigHelper $configHelper
     * @param ContactRepositoryInterface $contactRepository
     * @param Logger $logger
     * @param CreateUpdateMessageBuilder $createUpdateMessageBuilder
     * @param PublisherInterface $publisher
     */
    public function __construct(
        ConfigHelper $configHelper,
        ContactRepositoryInterface $contactRepository,
        Logger $logger,
        CreateUpdateMessageBuilder $createUpdateMessageBuilder,
        PublisherInterface $publisher
    ) {
        $this->configHelper = $configHelper;
        $this->contactRepository = $contactRepository;
        $this->logger = $logger;
        $this->createUpdateMessageBuilder = $createUpdateMessageBuilder;
        $this->publisher = $publisher;
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

        if ($subscriber->getData('customer_id')) {
            return;
        }

        try {
            $contact = $this->contactRepository->getOrCreateBySubscriber($subscriber);
        } catch (CouldNotSaveException $e) {
            $this->logger->critical($e);
            return;
        }

        $this->publisher->publish(
            Topics::CONTACT_CREATE_UPDATE,
            $this->createUpdateMessageBuilder->buildWithSubscriber($contact, $subscriber)
        );
    }
}
