<?php
declare(strict_types=1);
/**
 */

namespace CommerceLeague\ActiveCampaign\Model\ResourceModel\Magento;

use CommerceLeague\ActiveCampaign\Setup\SchemaInterface;
use Magento\Customer\Model\ResourceModel\Customer\Collection as ExtendCustomerCollection;

/**
 * Class CustomerCollection
 * @codeCoverageIgnore
 */
class CustomerCollection extends ExtendCustomerCollection
{
    /**
     * @inheritDoc
     */
    protected function _initSelect()
    {
        parent::_initSelect();

        $this->getSelect()->joinLeft(
            ['acc' => $this->_resource->getTableName(SchemaInterface::CUSTOMER_TABLE)],
            'acc.magento_customer_id = e.entity_id',
            ['acc.activecampaign_id']
        );

        return $this;
    }

    /**
     * @param string $email
     * @return CustomerCollection
     */
    public function addEmailFilter(string $email): self
    {
        $this->getSelect()->where('e.email = ?', $email);
        return $this;
    }

    /**
     * @return CustomerCollection
     */
    public function addOmittedFilter(): self
    {
        $this->getSelect()->where('acc.activecampaign_id IS NULL');
        return $this;
    }
}
