<?php
declare(strict_types=1);
/**
 */

namespace CommerceLeague\ActiveCampaign\Model\ResourceModel\ActiveCampaign\Customer;

use CommerceLeague\ActiveCampaign\Model\ActiveCampaign\Customer;
use CommerceLeague\ActiveCampaign\Model\ResourceModel\ActiveCampaign\Customer as CustomerResource;
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
        $this->_init(Customer::class, CustomerResource::class);
    }
}
