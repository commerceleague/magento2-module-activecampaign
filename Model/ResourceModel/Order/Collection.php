<?php
declare(strict_types=1);
/**
 */

namespace CommerceLeague\ActiveCampaign\Model\ResourceModel\Order;

use CommerceLeague\ActiveCampaign\Setup\SchemaInterface;
use Magento\Sales\Model\ResourceModel\Order\Collection as ExtendCollection;

/**
 * Class Collection
 * @codeCoverageIgnore
 */
class Collection extends ExtendCollection
{
    /**
     * @inheritDoc
     */
    protected function _initSelect()
    {
        parent::_initSelect();

        $this->getSelect()->joinLeft(
            ['ac_order' => $this->_resource->getTable(SchemaInterface::ORDER_TABLE)],
            'ac_order.magento_quote_id = main_table.quote_id',
            ['ac_order.activecampaign_id']
        );

        return $this;
    }

    /**
     * @return Collection
     */
    public function addExcludeGuestFilter(): self
    {
        $this->getSelect()->where('main_table.customer_is_guest = 0');
        return $this;
    }

    /**
     * @param int $orderId
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
}
