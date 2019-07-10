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
            ['ac_contact' => $this->_resource->getTableName(SchemaInterface::CONTACT_TABLE)],
            'ac_contact.email = e.email',
            ['ac_contact.activecampaign_id']
        );

        $this->getSelect()->joinLeft(
            ['ac_customer' => $this->_resource->getTableName(SchemaInterface::CUSTOMER_TABLE)],
            'ac_customer.magento_customer_id = e.entity_id',
            ['ac_customer.activecampaign_id']
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
    public function addContactOmittedFilter(): self
    {
        $this->getSelect()->where('ac_contact.activecampaign_id IS NULL');
        return $this;
    }

    /**
     * @return CustomerCollection
     */
    public function addCustomerOmittedFilter(): self
    {
        $this->getSelect()->where('ac_customer.activecampaign_id IS NULL');
        return $this;
    }
}
