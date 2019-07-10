<?php
declare(strict_types=1);
/**
 */

namespace CommerceLeague\ActiveCampaign\Model\ResourceModel\ActiveCampaign;

use CommerceLeague\ActiveCampaign\Api\Data\OrderInterface;
use CommerceLeague\ActiveCampaign\Setup\SchemaInterface;
use Magento\Framework\Model\ResourceModel\Db\AbstractDb;

/**
 * Class Order
 * @codeCoverageIgnore
 */
class Order extends AbstractDb
{
    /**
     * @inheritDoc
     */
    protected function _construct()
    {
        $this->_init(SchemaInterface::ORDER_TABLE, OrderInterface::ENTITY_ID);
    }
}
