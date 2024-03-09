<?php
declare(strict_types=1);
/**
 */

namespace CommerceLeague\ActiveCampaign\Model\ResourceModel\GuestCustomer;

use CommerceLeague\ActiveCampaign\Setup\SchemaInterface;
use Magento\Sales\Model\ResourceModel\Order\Collection as ExtendCollection;

/**
 * Class Collection
 *
 * @codeCoverageIgnore
 */
class Collection extends ExtendCollection
{

    /**
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
        $this->getSelect()->where('ac_guest.activecampaign_id IS NULL');
        return $this;
    }

    public function addEmailFilter(string $email): self
    {
        $this->getSelect()->where('ac_guest.email = ?', $email);
        return $this;
    }

    protected function _initSelect(): Collection
    {
        parent::_initSelect();

        $this->getSelect()->joinLeft(
            ['ac_guest' => $this->_resource->getTable(SchemaInterface::GUEST_CUSTOMER_TABLE)],
            'ac_guest.email = main_table.customer_email AND main_table.customer_is_guest = 1',
            ['ac_guest.activecampaign_id']
        );

        return $this;
    }
}
