<?php
declare(strict_types=1);
/**
 */

namespace CommerceLeague\ActiveCampaign\Model\ResourceModel\Connection;

use CommerceLeague\ActiveCampaign\Model\Connection;
use CommerceLeague\ActiveCampaign\Model\ResourceModel\Connection as ConnectionResource;
use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;

/**
 * Class Collection
 */
class Collection extends AbstractCollection
{
    /**
     * @inheritDoc
     */
    protected function _construct()
    {
        $this->_init(Connection::class, ConnectionResource::class);
    }
}
