<?php
declare(strict_types=1);
/**
 */

namespace CommerceLeague\ActiveCampaign\Model\ResourceModel\ActiveCampaign;

use CommerceLeague\ActiveCampaign\Api\Data\GuestCustomerInterface;
use CommerceLeague\ActiveCampaign\Setup\SchemaInterface;
use Magento\Framework\Model\ResourceModel\Db\AbstractDb;

/**
 * Class GuestCustomer
 *
 * @codeCoverageIgnore
 */
class GuestCustomer extends AbstractDb
{

    /**
     * @inheritDoc
     */
    protected function _construct()
    {
        $this->_init(SchemaInterface::GUEST_CUSTOMER_TABLE, GuestCustomerInterface::ENTITY_ID);
    }
}


