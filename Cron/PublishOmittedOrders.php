<?php
declare(strict_types=1);
/**
 */

namespace CommerceLeague\ActiveCampaign\Cron;

use CommerceLeague\ActiveCampaign\Api\CronInterface;
use CommerceLeague\ActiveCampaign\Helper\Config as ConfigHelper;
use CommerceLeague\ActiveCampaign\MessageQueue\Topics;
use CommerceLeague\ActiveCampaign\Model\ResourceModel\Order\CollectionFactory as OrderCollectionFactory;
use CommerceLeague\ActiveCampaign\Model\ResourceModel\Order\Collection as OrderCollection;
use Magento\Framework\MessageQueue\PublisherInterface;
use Psr\Log\LoggerInterface;

/**
 * Class PublishOmittedOrders
 */
class PublishOmittedOrders implements CronInterface
{

    public function __construct(private readonly ConfigHelper           $configHelper,
                                private readonly OrderCollectionFactory $orderCollectionFactory,
                                private readonly PublisherInterface     $publisher,
                                private readonly LoggerInterface        $logger
    ) {
    }


    /**
     * @return array<int, string>
     */
    public function getOrderIds(): array
    {
        /** @var OrderCollection $orderCollection */
        $orderCollection = $this->orderCollectionFactory->create();
//        $orderCollection->addExcludeGuestFilter();
        if (!$this->configHelper->isRetryAllOmittedEnabled()) {
            $orderCollection->addExportFilterOrderStatus();
            $orderCollection->addExportFilterStartDate();
        }
        $orderCollection->addOmittedFilter();
        $orderCollection->addNotDeadLetteredFilter();

        return $orderCollection->getAllIds();
    }

    /**
     * @inheritDoc
     */
    public function run(): void
    {
        if (!$this->configHelper->isEnabled() || !$this->configHelper->isOrderExportEnabled()) {
            return;
        }

        $orderIds = $this->getOrderIds();

        if ($this->configHelper->isRetryAllOmittedEnabled()) {
            $this->logger->warning(sprintf(
                'ActiveCampaign retry_all_omitted is ON — re-publishing %d omitted order record(s) '
                . 'without the scope bound',
                count($orderIds)
            ));
        }

        foreach ($orderIds as $orderId) {
            $this->publisher->publish(
                Topics::SALES_ORDER_EXPORT,
                json_encode(['magento_order_id' => $orderId], JSON_THROW_ON_ERROR)
            );
        }
    }
}
