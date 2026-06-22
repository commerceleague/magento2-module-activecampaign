<?php
declare(strict_types=1);
/**
 */

namespace CommerceLeague\ActiveCampaign\MessageQueue\Quote;

use CommerceLeague\ActiveCampaign\Api\OrderRepositoryInterface;
use CommerceLeague\ActiveCampaign\Gateway\Client;
use CommerceLeague\ActiveCampaign\Logger\Logger;
use CommerceLeague\ActiveCampaign\MessageQueue\AbstractConsumer;
use CommerceLeague\ActiveCampaign\MessageQueue\ConsumerInterface;
use CommerceLeague\ActiveCampaignApi\Exception\HttpException;
use CommerceLeague\ActiveCampaignApi\Exception\NotFoundHttpException;
use Magento\Framework\Exception\NoSuchEntityException;

/**
 * Class DeleteAbandonedCartConsumer
 *
 * Removes a stale abandoned-cart "order" from ActiveCampaign (and the local
 * mapping row) after Magento's quote-cleanup cron deleted the never-converted
 * cart. A 404 from AC means the entity is already gone and is treated as
 * success so the local row is still cleaned up.
 */
class DeleteAbandonedCartConsumer extends AbstractConsumer implements ConsumerInterface
{

    public function __construct(
        Logger $logger,
        private readonly Client $client,
        private readonly OrderRepositoryInterface $orderRepository
    ) {
        parent::__construct($logger);
    }

    public function consume(string $message): void
    {
        $message = json_decode($message, true, 512, JSON_THROW_ON_ERROR);

        $activeCampaignId = (int)$message['activecampaign_id'];
        $entityId = (int)$message['entity_id'];

        try {
            $this->client->getOrderApi()->delete($activeCampaignId);
        } catch (NotFoundHttpException $e) {
            // Already gone from ActiveCampaign; treat as success and clean up locally.
            $this->getLogger()->info(sprintf(
                'ActiveCampaign abandoned cart %d already absent (404); deleting local row %d',
                $activeCampaignId,
                $entityId
            ));
        } catch (HttpException $e) {
            $this->logFailure(
                'abandoned_cart',
                $entityId,
                null,
                $e->getCode(),
                'delete_failed',
                $e->getMessage()
            );
            // Keep the local row so the message can retry.
            return;
        }

        try {
            $this->orderRepository->deleteById($entityId);
        } catch (NoSuchEntityException $e) {
            // Local row already gone; nothing left to do.
            $this->getLogger()->info(sprintf(
                'ActiveCampaign abandoned cart local row %d already absent',
                $entityId
            ));
        }
    }

    /**
     * @inheritDoc
     */
    function processDuplicateEntity(array $request, string $key): array
    {
        return [];
    }
}
