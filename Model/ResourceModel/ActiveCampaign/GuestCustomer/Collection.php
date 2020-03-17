<?php
declare(strict_types=1);
/**
 */

namespace CommerceLeague\ActiveCampaign\Model\ResourceModel\ActiveCampaign\GuestCustomer;

use CommerceLeague\ActiveCampaign\Helper\Config;
use CommerceLeague\ActiveCampaign\Model\ActiveCampaign\GuestCustomer;
use CommerceLeague\ActiveCampaign\Model\ResourceModel\ActiveCampaign\GuestCustomer as CustomerResource;
use Magento\Framework\Data\Collection\Db\FetchStrategyInterface;
use Magento\Framework\Data\Collection\EntityFactoryInterface;
use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Framework\Event\ManagerInterface;
use Magento\Framework\Model\ResourceModel\Db\AbstractDb;
use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;
use Psr\Log\LoggerInterface;

/**
 * Class Collection
 *
 * @codeCoverageIgnore
 */
class Collection extends AbstractCollection
{

    /**
     * @var Config
     */
    private $configHelper;

    public function __construct(
        EntityFactoryInterface $entityFactory, LoggerInterface $logger,
        FetchStrategyInterface $fetchStrategy,
        ManagerInterface $eventManager,
        AdapterInterface $connection = null,
        AbstractDb $resource = null,
        Config $configHelper
    ) {
        $this->configHelper = $configHelper;
        parent::__construct($entityFactory, $logger, $fetchStrategy, $eventManager, $connection, $resource);
    }

    /**
     * @return Collection
     */
    public function addOmittedFilter(): self
    {
        $this->getSelect()->where('main_table.activecampaign_id IS NULL');
        return $this;
    }

    /**
     * @return Collection
     */
    public function addExportFilterOrderStatus(): self
    {
        $orderStatuses = $this->configHelper->getOrderExportStatuses();
        if ($orderStatuses) {
            $this->getSelect()->where('sales_order.status IN (?)', $orderStatuses);
        }
        return $this;
    }

    public function addExportFilterStartDate()
    {
        $startDateFilter = $this->configHelper->getOrderExportStartDate();
        if ($startDateFilter) {
            $this->getSelect()->where('sales_order.created_at > ?', $startDateFilter);
        }
        return $this;
    }

    /**
     * @inheritDoc
     */
    protected function _construct()
    {
        $this->_init(GuestCustomer::class, CustomerResource::class);
    }

    /**
     * @inheritDoc
     */
    protected function _initSelect()
    {
        parent::_initSelect();

        $this->getSelect()->joinLeft(
            ['sales_order' => $this->_resource->getTable('sales_order')],
            'sales_order.customer_email = main_table.email AND sales_order.customer_is_guest = 1',
            ['sales_order.customer_is_guest', 'sales_order.customer_email']
        );

        return $this;
    }
}
