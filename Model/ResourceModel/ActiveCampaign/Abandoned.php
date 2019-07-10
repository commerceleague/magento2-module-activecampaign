<?php
declare(strict_types=1);
/**
 */

namespace CommerceLeague\ActiveCampaign\Model\ResourceModel\ActiveCampaign;

use CommerceLeague\ActiveCampaign\Api\Data\AbandonedInterface;
use CommerceLeague\ActiveCampaign\Setup\SchemaInterface;
use Magento\Framework\Model\ResourceModel\Db\AbstractDb;

/**
 * Class Abandoned
 * @codeCoverageIgnore
 */
class Abandoned extends AbstractDb
{
    /**
     * @inheritDoc
     */
    protected function _construct()
    {
        $this->_init(SchemaInterface::ABANDONED_TABLE, AbandonedInterface::ENTITY_ID);
    }
}
