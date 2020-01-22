<?php
declare(strict_types=1);
/**
 * Copyright © André Flitsch. All rights reserved.
 * See license.md for license details.
 */

namespace CommerceLeague\ActiveCampaign\Observer\Newsletter;

use CommerceLeague\ActiveCampaign\Api\Data\ContactInterface;
use CommerceLeague\ActiveCampaign\Helper\Config;
use CommerceLeague\ActiveCampaign\MessageQueue\Topics;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\MessageQueue\PublisherInterface;

/**
 * Class AssignNewsletterSubscriberToListObserver
 *
 * @package CommerceLeague\ActiveCampaign\Observer\Newsletter
 */
class AssignNewsletterSubscriberToListObserver implements ObserverInterface
{

    /**
     * @var Config
     */
    private $configHelper;

    /**
     * @var PublisherInterface
     */
    private $publisher;

    /**
     * AssignNewsletterSubscriberToListObserver constructor.
     *
     * @param Config             $configHelper
     * @param PublisherInterface $publisher
     */
    public function __construct(
        Config $configHelper,
        PublisherInterface $publisher
    ) {
        $this->configHelper = $configHelper;
        $this->publisher    = $publisher;
    }

    /**
     * @param Observer $observer
     *
     * @return void
     */
    public function execute(Observer $observer)
    {
        if (!$this->configHelper->isEnabled() || !$this->configHelper->isContactExportEnabled()) {
            return;
        }

        /** @var ContactInterface $contact */
        $contact = $observer->getData('contact');

        $listId = $this->configHelper->getNewsletterSubscriberList();
        if ($listId) {
            $this->publisher->publish(
                Topics::ASSIGN_NEWSLETTER_SUBSCRIBER_TO_LIST,
                json_encode(
                    [
                        'contact_id' => $contact->getId(),
                        'list_id'    => $listId
                    ]
                )
            );
        }
    }
}