<?php
declare(strict_types=1);
/**
 */

namespace CommerceLeague\ActiveCampaign\Observer\Newsletter;

use CommerceLeague\ActiveCampaign\MessageQueue\Topics;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\MessageQueue\PublisherInterface;
use Magento\Newsletter\Model\Subscriber;
use CommerceLeague\ActiveCampaign\Helper\Config as ConfigHelper;

/**
 * Class ExportContactObserver
 */
class ExportContactObserver implements ObserverInterface
{
    public function __construct(private readonly ConfigHelper $configHelper, private readonly PublisherInterface $publisher)
    {
    }

    /**
     * @inheritDoc
     */
    public function execute(Observer $observer)
    {
        if (!$this->configHelper->isEnabled() || !$this->configHelper->isContactExportEnabled()) {
            return;
        }

        /** @var Subscriber $subscriber */
        $subscriber = $observer->getEvent()->getData('subscriber');

        if ($subscriber->getData('customer_id')) {
            return;
        }

        $this->publisher->publish(
            Topics::NEWSLETTER_CONTACT_EXPORT,
            json_encode(['email' => $subscriber->getEmail()], JSON_THROW_ON_ERROR)
        );
    }
}
