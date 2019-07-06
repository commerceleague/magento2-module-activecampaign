<?php
declare(strict_types=1);
/**
 */

namespace CommerceLeague\ActiveCampaign\MessageQueue;

use CommerceLeague\ActiveCampaign\Api\Data\OrderInterface;
use CommerceLeague\ActiveCampaign\Api\OrderRepositoryInterface;
use CommerceLeague\ActiveCampaign\Gateway\Client;
use CommerceLeague\ActiveCampaign\Logger\Logger;
use CommerceLeague\ActiveCampaignApi\Exception\HttpException;
use Magento\Framework\Exception\CouldNotSaveException;

/**
 * Class ExportOrderConsumer
 */
class ExportOrderConsumer
{
    /**
     * @var OrderRepositoryInterface
     */
    private $orderRepository;

    /**
     * @var Logger
     */
    private $logger;

    /**
     * @var Client
     */
    private $client;

    /**
     * @param OrderRepositoryInterface $orderRepository
     * @param Logger $logger
     * @param Client $client
     */
    public function __construct(
        OrderRepositoryInterface $orderRepository,
        Logger $logger,
        Client $client
    ) {
        $this->orderRepository = $orderRepository;
        $this->logger = $logger;
        $this->client = $client;
    }

    /**
     * @param string $message
     * @throws CouldNotSaveException
     */
    public function consume(string $message): void
    {
        $message = json_decode($message, true);
        $order = $this->orderRepository->getOrCreateByMagentoOrderId($message['magento_order_id']);

        try {
            $apiResponse = $this->performApiRequest($order, $message['request']);
        } catch (HttpException $e) {
            $this->logger->err($e->getMessage());
            return;
        }

        $order->setActiveCampaignId($apiResponse['ecomOrder']['id']);

        $this->orderRepository->save($order);
    }

    /**
     * @param OrderInterface $order
     * @param array $request
     * @return array
     */
    private function performApiRequest(OrderInterface $order, array $request): array
    {
        if ($activeCampaignId = $order->getActiveCampaignId()) {
            return $this->client->getOrderApi()->update((int)$activeCampaignId, ['ecomOrder' => $request]);
        } else {
            return $this->client->getOrderApi()->create(['ecomOrder' => $request]);
        }
    }
}
