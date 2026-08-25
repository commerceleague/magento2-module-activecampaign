<?php
declare(strict_types=1);
/**
 */

namespace CommerceLeague\ActiveCampaign\Observer\Customer;

use CommerceLeague\ActiveCampaign\Helper\Config as ConfigHelper;
use CommerceLeague\ActiveCampaign\MessageQueue\Topics;
use Magento\Customer\Model\Customer as MagentoCustomer;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\MessageQueue\PublisherInterface;

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

    public function execute(Observer $observer): void
    {
        if (!$this->configHelper->isEnabled() || !$this->configHelper->isContactExportEnabled()) {
            return;
        }

        /** @var MagentoCustomer $magentoCustomer */
        $magentoCustomer = $observer->getEvent()->getData('customer');
        $payload         = ['magento_customer_id' => $magentoCustomer->getId()];

        $this->publisher->publish(Topics::CUSTOMER_CONTACT_EXPORT, json_encode($payload, JSON_THROW_ON_ERROR));

        $this->afterPublish(Topics::CUSTOMER_CONTACT_EXPORT, $payload);
    }

    /**
     * @param array<mixed> $payload the array passed to json_encode before publishing
     */
    protected function afterPublish(string $topic, array $payload): void
    {
    }
}
