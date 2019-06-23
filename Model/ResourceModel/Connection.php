<?php
declare(strict_types=1);
/**
 */

namespace CommerceLeague\ActiveCampaign\Model\ResourceModel;

use CommerceLeague\ActiveCampaign\Api\Data\ConnectionInterface;
use CommerceLeague\ActiveCampaign\Setup\SchemaInterface;
use Magento\Framework\Model\ResourceModel\Db\AbstractDb;

/**
 * Class Connection
 */
class Connection extends AbstractDb
{
    /**
     * @inheritDoc
     */
    protected function _construct()
    {
        $this->_init(SchemaInterface::CONNECTION_TABLE, ConnectionInterface::CONNECTION_ID);
    }
}
