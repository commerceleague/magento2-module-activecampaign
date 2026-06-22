<?php
declare(strict_types=1);
/**
 */

namespace CommerceLeague\ActiveCampaign\Observer\Quote;

use CommerceLeague\ActiveCampaign\Api\OrderRepositoryInterface;
use CommerceLeague\ActiveCampaign\Helper\Config as ConfigHelper;
use CommerceLeague\ActiveCampaign\MessageQueue\Topics;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\MessageQueue\PublisherInterface;
use Magento\Quote\Model\Quote;

/**
 * Class CleanupAbandonedCartObserver
 *
 * When Magento's quote-cleanup cron deletes an expired, never-converted cart it
 * fires sales_quote_delete_after. The matching ActiveCampaign ecommerce "order"
 * (the abandoned cart) would otherwise linger in AC forever. This observer:
 *  - publishes a delete for a pure abandoned cart (synced to AC, never converted),
 *  - just drops the local mapping for a converted cart (now a real order in AC),
 *  - drops the local mapping when there is no AC id (never synced).
 */
class CleanupAbandonedCartObserver implements ObserverInterface
{

    public function __construct(
        private readonly ConfigHelper $configHelper,
        private readonly OrderRepositoryInterface $orderRepository,
        private readonly PublisherInterface $publisher
    ) {
    }

    /**
     * @inheritDoc
     */
    public function execute(Observer $observer): void
    {
        if (!$this->configHelper->isEnabled() || !$this->configHelper->isAbandonedCartExportEnabled()) {
            return;
        }

        /** @var Quote|null $quote */
        $quote = $observer->getEvent()->getData('quote');

        if ($quote === null || !$quote->getId()) {
            return;
        }

        try {
            $acOrder = $this->orderRepository->getByMagentoQuoteId((int)$quote->getId());
        } catch (NoSuchEntityException $e) {
            // No AC row for this quote; nothing to clean up.
            return;
        }

        $activeCampaignId = $acOrder->getActiveCampaignId();
        $entityId = (int)$acOrder->getId();

        // Never synced to AC: just drop the stale local mapping; nothing to delete remotely.
        if (empty($activeCampaignId)) {
            $this->deleteLocalRow($entityId);
            return;
        }

        // Converted to a real order: do NOT delete from AC, only drop the stale local mapping.
        if (!empty($acOrder->getMagentoOrderId())) {
            $this->deleteLocalRow($entityId);
            return;
        }

        // Pure abandoned cart: delete from AC (and the local row) via the consumer.
        $this->publisher->publish(
            Topics::QUOTE_DELETE_ABANDONED_CART,
            json_encode(
                ['activecampaign_id' => (int)$activeCampaignId, 'entity_id' => $entityId],
                JSON_THROW_ON_ERROR
            )
        );
    }

    private function deleteLocalRow(int $entityId): void
    {
        try {
            $this->orderRepository->deleteById($entityId);
        } catch (NoSuchEntityException $e) {
            // Already gone; nothing to do.
            return;
        }
    }
}
