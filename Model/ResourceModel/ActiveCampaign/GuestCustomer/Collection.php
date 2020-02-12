<?php
declare(strict_types=1);
/**
 */

namespace CommerceLeague\ActiveCampaign\Model\ResourceModel\ActiveCampaign\GuestCustomer;

use CommerceLeague\ActiveCampaign\Model\ActiveCampaign\GuestCustomer;
use CommerceLeague\ActiveCampaign\Model\ResourceModel\ActiveCampaign\GuestCustomer as CustomerResource;
use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;

/**
 * Class Collection
 *
 * @codeCoverageIgnore
 */
class Collection extends AbstractCollection
{

    /**
     * @return Collection
     */
    public function addOmittedFilter(): self
    {
        $this->getSelect()->where('main_table.activecampaign_id IS NULL');
        return $this;
    }

    /**
     * @inheritDoc
     */
    protected function _construct()
    {
        $this->_init(GuestCustomer::class, CustomerResource::class);
    }
}
