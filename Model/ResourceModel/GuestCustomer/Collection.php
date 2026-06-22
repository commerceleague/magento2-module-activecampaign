<?php
declare(strict_types=1);
/**
 */

namespace CommerceLeague\ActiveCampaign\Model\ResourceModel\GuestCustomer;

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
        EntityFactory $entityFactory,
        LoggerInterface $logger,
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
        $this->getSelect()->where('ac_guest.activecampaign_id IS NULL');
        return $this;
    }

    public function addEmailFilter(string $email): self
    {
        $this->getSelect()->where('ac_guest.email = ?', $email);
        return $this;
    }

    /**
     * Mirror of the omitted-cron scope bound: only guest orders whose status is
     * within the configured order export statuses.
     *
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

    /**
     * Mirror of the omitted-cron scope bound: only guest orders created after the
     * configured export start date.
     *
     * @return Collection
     */
    public function addExportFilterStartDate(): self
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
            ['ac_guest' => $this->_resource->getTable(SchemaInterface::GUEST_CUSTOMER_TABLE)],
            'ac_guest.email = main_table.customer_email AND main_table.customer_is_guest = 1',
            ['ac_guest.activecampaign_id']
        );

        return $this;
    }
}
