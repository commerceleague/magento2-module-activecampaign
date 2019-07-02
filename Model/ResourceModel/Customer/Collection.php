<?php
declare(strict_types=1);
/**
 */

namespace CommerceLeague\ActiveCampaign\Model\ResourceModel\Customer;

use CommerceLeague\ActiveCampaign\Model\Customer;
use CommerceLeague\ActiveCampaign\Model\ResourceModel\Customer as CustomerResource;
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
        $this->_init(Customer::class, CustomerResource::class);
    }
}
