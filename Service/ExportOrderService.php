<?php
declare(strict_types=1);
/**
 */

namespace CommerceLeague\ActiveCampaign\Service;

use CommerceLeague\ActiveCampaign\Gateway\Request\OrderBuilder as OrderRequestBuilder;
use CommerceLeague\ActiveCampaign\MessageQueue\Topics;
use Magento\Framework\MessageQueue\PublisherInterface;
use Magento\Sales\Model\Order as MagentoOrder;

class ExportOrderService
{
    /**
     * @var OrderRequestBuilder
     */
    private $orderRequestBuilder;

    /**
     * @var PublisherInterface
     */
    private $publisher;

    /**
     * @param OrderRequestBuilder $orderRequestBuilder
     * @param PublisherInterface $publisher
     */
    public function __construct(
        OrderRequestBuilder $orderRequestBuilder,
        PublisherInterface $publisher
    ) {
        $this->orderRequestBuilder = $orderRequestBuilder;
        $this->publisher = $publisher;
    }

    public function export(MagentoOrder $magentoOrder): void
    {
        $data = [
            'magento_order_id' => $magentoOrder->getId(),
            'request' => $this->orderRequestBuilder->build($magentoOrder)
        ];

        $this->publisher->publish(Topics::ORDER_EXPORT, json_encode($data));
    }
}
