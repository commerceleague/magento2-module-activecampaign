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
 *
 * afterPublish() is a no-op extension point (protected rather than private
 * specifically so an integrator's preference-bound subclass can observe the
 * publish — e.g. an audit trail — without duplicating execute()).
 */
class ExportContactObserver implements ObserverInterface
{
    public function __construct(
        protected readonly ConfigHelper $configHelper,
        protected readonly PublisherInterface $publisher
    ) {
    }

    /**
     * @inheritDoc
     */
    public function execute(Observer $observer): void
    {
        if (!$this->configHelper->isEnabled() || !$this->configHelper->isContactExportEnabled()) {
            return;
        }

        /** @var Subscriber $subscriber */
        $subscriber = $observer->getEvent()->getData('subscriber');

        if ($subscriber->getStatus() != Subscriber::STATUS_SUBSCRIBED) {
            return;
        }

        $payload = ['email' => $subscriber->getEmail()];

        $this->publisher->publish(Topics::NEWSLETTER_CONTACT_EXPORT, json_encode($payload, JSON_THROW_ON_ERROR));

        $this->afterPublish(Topics::NEWSLETTER_CONTACT_EXPORT, $payload);
    }

    /**
     * @param array<mixed> $payload the array passed to json_encode before publishing
     */
    protected function afterPublish(string $topic, array $payload): void
    {
    }
}
