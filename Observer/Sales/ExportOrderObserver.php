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
 */
class ExportOrderObserver implements ObserverInterface
{

    public function __construct(private readonly ConfigHelper $configHelper, private readonly PublisherInterface $publisher)
    {
    }

    /**
     * @inheritDoc
     */
    public function execute(Observer $observer)
    {
        if (!$this->configHelper->isEnabled() || !$this->configHelper->isOrderExportEnabled()) {
            return;
        }

        /** @var MagentoOrder $magentoOrder */
        $magentoOrder = $observer->getEvent()->getData('order');

        if ($magentoOrder->getCustomerIsGuest()) {
            $guestData = json_encode(
                [
                    'magento_customer_id' => null,
                    'customer_is_guest' => true,
                    'customer_data'     => [
                        GuestCustomerInterface::FIRSTNAME => $magentoOrder->getCustomerFirstname(),
                        GuestCustomerInterface::LASTNAME  => $magentoOrder->getCustomerLastname(),
                        GuestCustomerInterface::EMAIL     => $magentoOrder->getCustomerEmail()
                    ]
                ]
            );

            // export guest contact
            $this->publisher->publish(Topics::CUSTOMER_CONTACT_EXPORT, $guestData);

            // export guest customer
            $this->publisher->publish(Topics::GUEST_CUSTOMER_EXPORT, $guestData);
        }

        $this->publisher->publish(
            Topics::SALES_ORDER_EXPORT,
            json_encode(['magento_order_id' => $magentoOrder->getId()], JSON_THROW_ON_ERROR)
        );
    }
}
