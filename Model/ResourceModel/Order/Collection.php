<?php
declare(strict_types=1);
/**
 */

namespace CommerceLeague\ActiveCampaign\Model\ResourceModel\Order;

use CommerceLeague\ActiveCampaign\Model\Order;
use CommerceLeague\ActiveCampaign\Model\ResourceModel\Order as OrderResource;
use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;

/**
 * Class Collection
 * @codeCoverageIgnore
 */
class Collection extends AbstractCollection
{
    /**
     * @inheritDoc
     */
    protected function _construct()
    {
        $this->_init(Order::class, OrderResource::class);
    }
}
