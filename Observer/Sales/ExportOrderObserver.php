<?php
declare(strict_types=1);
/**
 */

namespace CommerceLeague\ActiveCampaign\Observer\Sales;

use CommerceLeague\ActiveCampaign\Api\Data\GuestCustomerInterface;
use CommerceLeague\ActiveCampaign\Helper\Config as ConfigHelper;
use CommerceLeague\ActiveCampaign\MessageQueue\Topics;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\MessageQueue\PublisherInterface;
use Magento\Sales\Model\Order as MagentoOrder;

/**
 * Class ExportOrderObserver
 *
 * afterPublish() is a no-op extension point (protected rather than private
 * specifically so an integrator's preference-bound subclass can observe each
 * publish — e.g. an audit trail — without duplicating execute()). Called once
 * per publish() call above, so a guest order fires it for contact,
 * guest_customer, and order in that sequence.
 */
class ExportOrderObserver implements ObserverInterface
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
        if (!$this->configHelper->isEnabled() || !$this->configHelper->isOrderExportEnabled()) {
            return;
        }

        /** @var MagentoOrder $magentoOrder */
        $magentoOrder = $observer->getEvent()->getData('order');

        if ($magentoOrder->getCustomerIsGuest()) {
            $guestPayload = [
                'magento_customer_id' => null,
                'customer_is_guest' => true,
                'customer_data'     => [
                    GuestCustomerInterface::FIRSTNAME => $magentoOrder->getCustomerFirstname(),
                    GuestCustomerInterface::LASTNAME  => $magentoOrder->getCustomerLastname(),
                    GuestCustomerInterface::EMAIL     => $magentoOrder->getCustomerEmail()
                ]
            ];
            $guestData = json_encode($guestPayload);

            // export guest contact
            $this->publisher->publish(Topics::CUSTOMER_CONTACT_EXPORT, $guestData);
            $this->afterPublish(Topics::CUSTOMER_CONTACT_EXPORT, $guestPayload);

            // export guest customer
            $this->publisher->publish(Topics::GUEST_CUSTOMER_EXPORT, $guestData);
            $this->afterPublish(Topics::GUEST_CUSTOMER_EXPORT, $guestPayload);
        }

        $orderPayload = ['magento_order_id' => $magentoOrder->getId()];

        $this->publisher->publish(Topics::SALES_ORDER_EXPORT, json_encode($orderPayload, JSON_THROW_ON_ERROR));
        $this->afterPublish(Topics::SALES_ORDER_EXPORT, $orderPayload);
    }

    /**
     * @param array<mixed> $payload the array passed to json_encode before publishing
     */
    protected function afterPublish(string $topic, array $payload): void
    {
    }
}
