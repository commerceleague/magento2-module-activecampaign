<?php
declare(strict_types=1);
/**
 */

namespace CommerceLeague\ActiveCampaign\Model\ResourceModel\Order;

use CommerceLeague\ActiveCampaign\Helper\Config;
use CommerceLeague\ActiveCampaign\Setup\SchemaInterface;
use Magento\Framework\Data\Collection\Db\FetchStrategyInterface;
use Magento\Framework\Data\Collection\EntityFactory;
use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Framework\DB\Helper;
use Magento\Framework\Event\ManagerInterface;
use Magento\Framework\Model\ResourceModel\Db\AbstractDb;
use Magento\Framework\Model\ResourceModel\Db\VersionControl\Snapshot;
use Magento\Sales\Model\ResourceModel\Order\Collection as ExtendCollection;
use Psr\Log\LoggerInterface;

/**
 * Class Collection
 *
 * @codeCoverageIgnore
 */
class Collection extends ExtendCollection
{

    /**
     * @param EntityFactory          $entityFactory
     * @param LoggerInterface        $logger
     * @param FetchStrategyInterface $fetchStrategy
     * @param ManagerInterface       $eventManager
     * @param Snapshot               $entitySnapshot
     * @param Helper                 $coreResourceHelper
     * @param Config                 $configHelper
     * @param AdapterInterface|null  $connection
     * @param AbstractDb|null        $resource
     */
    public function __construct(
        EntityFactory $entityFactory, LoggerInterface $logger,
        FetchStrategyInterface $fetchStrategy,
        ManagerInterface $eventManager,
        Snapshot $entitySnapshot,
        Helper $coreResourceHelper,
        private readonly Config $configHelper,
        AdapterInterface $connection = null,
        AbstractDb $resource = null

    ) {
        parent::__construct(
            $entityFactory,
            $logger,
            $fetchStrategy,
            $eventManager,
            $entitySnapshot,
            $coreResourceHelper,
            $connection,
            $resource
        );
    }

    /**
     * @return Collection
     */
    public function addIdFilter(int $orderId): self
    {
        $this->getSelect()->where('main_table.entity_id = ?', $orderId);
        return $this;
    }

    /**
     * @return Collection
     */
    public function addOmittedFilter(): self
    {
        $this->getSelect()->where('ac_order.activecampaign_id IS NULL');
        return $this;
    }

    /**
     * @return Collection
     */
    public function addExportFilterOrderStatus(): self
    {
        $orderStatuses = $this->configHelper->getOrderExportStatuses();
        if ($orderStatuses) {
            $this->getSelect()->where('main_table.status IN (?)', $orderStatuses);
        }
        return $this;
    }

    public function addExportFilterStartDate()
    {
        $startDateFilter = $this->configHelper->getOrderExportStartDate();
        if ($startDateFilter) {
            $this->getSelect()->where('main_table.created_at > ?', $startDateFilter);
        }
        return $this;
    }

    protected function _initSelect(): Collection
    {
        parent::_initSelect();

        $this->getSelect()->joinLeft(
            ['ac_order' => $this->_resource->getTable(SchemaInterface::ORDER_TABLE)],
            'ac_order.magento_quote_id = main_table.quote_id',
            ['ac_order.activecampaign_id']
        );

        return $this;
    }
}
