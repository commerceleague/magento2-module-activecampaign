<?php
declare(strict_types=1);
/**
 */

namespace CommerceLeague\ActiveCampaign\Model\ResourceModel\Subscriber;

use CommerceLeague\ActiveCampaign\Setup\SchemaInterface;
use Magento\Framework\DB\Select;
use Magento\Newsletter\Model\ResourceModel\Subscriber\Collection as ExtendSubscriberCollection;

/**
 * Class Collection
 * @codeCoverageIgnore
 */
class Collection extends ExtendSubscriberCollection
{
    /**
     * @inheritDoc
     */
    protected function _initSelect()
    {
        parent::_initSelect();

        $this->getSelect()->joinLeft(
            ['ac_contact' => $this->_resource->getTable(SchemaInterface::CONTACT_TABLE)],
            'ac_contact.email = main_table.subscriber_email',
            ['ac_contact.activecampaign_id']
        );
    }

    /**
     * @return Collection
     */
    public function excludeCustomers(): self
    {
        $this->getSelect()->where('main_table.customer_id = 0');
        return $this;
    }

    /**
     * @param string $email
     * @return Collection
     */
    public function addEmailFilter(string $email): self
    {
        $this->getSelect()->where('main_table.subscriber_email = ?', $email);
        return $this;
    }

    /**
     * @return Collection
     */
    public function addContactOmittedFilter(): self
    {
        $this->getSelect()->where('ac_contact.activecampaign_id IS NULL');
        return $this;
    }

    /**
     * @return array
     */
    public function getAllEmails(): array
    {
        $emailsSelect = clone $this->getSelect();
        $emailsSelect->reset(Select::ORDER);
        $emailsSelect->reset(Select::LIMIT_COUNT);
        $emailsSelect->reset(Select::LIMIT_OFFSET);
        $emailsSelect->reset(Select::COLUMNS);
        $emailsSelect->columns('subscriber_email', 'main_table');

        return $this->getConnection()->fetchCol($emailsSelect, $this->_bindParams);
    }
}
