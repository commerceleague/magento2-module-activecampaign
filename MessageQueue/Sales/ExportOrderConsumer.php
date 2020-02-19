<?php
declare(strict_types=1);
/**
 */

namespace CommerceLeague\ActiveCampaign\MessageQueue\Sales;

use CommerceLeague\ActiveCampaign\Api\Data\OrderInterface;
use CommerceLeague\ActiveCampaign\Api\OrderRepositoryInterface;
use CommerceLeague\ActiveCampaign\Gateway\Client;
use CommerceLeague\ActiveCampaign\Gateway\Request\OrderBuilder as OrderRequestBuilder;
use CommerceLeague\ActiveCampaign\Logger\Logger;
use CommerceLeague\ActiveCampaign\MessageQueue\AbstractConsumer;
use CommerceLeague\ActiveCampaign\MessageQueue\ConsumerInterface;
use CommerceLeague\ActiveCampaignApi\Exception\HttpException;
use CommerceLeague\ActiveCampaignApi\Exception\UnprocessableEntityHttpException;
use Exception;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Sales\Api\Data\OrderInterface as MagentoOrderInterface;
use Magento\Sales\Api\OrderRepositoryInterface as MagentoOrderRepositoryInterface;
use Magento\Sales\Model\Order as MagentoOrder;

/**
 * Class ExportOrderConsumer
 */
class ExportOrderConsumer extends AbstractConsumer implements ConsumerInterface
{

    /**
     * @var MagentoOrderRepositoryInterface
     */
    private $magentoOrderRepository;

    /**
     * @var OrderRepositoryInterface
     */
    private $orderRepository;

    /**
     * @var OrderRequestBuilder
     */
    private $orderRequestBuilder;

    /**
     * @var Client
     */
    private $client;

    /**
     * @param MagentoOrderRepositoryInterface $magentoOrderRepository
     * @param Logger                          $logger
     * @param OrderRepositoryInterface        $orderRepository
     * @param OrderRequestBuilder             $orderRequestBuilder
     * @param Client                          $client
     */
    public function __construct(
        MagentoOrderRepositoryInterface $magentoOrderRepository,
        Logger $logger,
        OrderRepositoryInterface $orderRepository,
        OrderRequestBuilder $orderRequestBuilder,
        Client $client
    ) {
        parent::__construct($logger);
        $this->magentoOrderRepository = $magentoOrderRepository;
        $this->orderRepository        = $orderRepository;
        $this->orderRequestBuilder    = $orderRequestBuilder;
        $this->client                 = $client;
    }

    /**
     * @param string $message
     *
     * @throws CouldNotSaveException
     * @throws Exception
     */
    public function consume(string $message): void
    {
        $message = json_decode($message, true);

        try {
            /** @var MagentoOrderInterface|MagentoOrder $magentoOrder */
            $magentoOrder = $this->magentoOrderRepository->get($message['magento_order_id']);
        } catch (NoSuchEntityException $e) {
            $this->logException($e);
            return;
        }

        $order   = $this->orderRepository->getOrCreateByMagentoQuoteId($magentoOrder->getQuoteId());
        $request = $this->orderRequestBuilder->build($magentoOrder);

        try {
            $apiResponse = $this->performApiRequest($order, $request);
        } catch (UnprocessableEntityHttpException $e) {
            $this->logUnprocessableEntityHttpException($e, $request);
            return;
        } catch (HttpException $e) {
            $this->logException($e);
            return;
        }

        $order->setActiveCampaignId($apiResponse['ecomOrder']['id']);

        $this->orderRepository->save($order);
    }

    /**
     * @param OrderInterface $order
     * @param array          $request
     *
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
