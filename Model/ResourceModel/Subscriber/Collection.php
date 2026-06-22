<?php
declare(strict_types=1);
/**
 */

namespace CommerceLeague\ActiveCampaign\Model\ResourceModel\Subscriber;

use CommerceLeague\ActiveCampaign\Api\Data\FailureTrackableInterface;
use CommerceLeague\ActiveCampaign\Setup\SchemaInterface;
use Magento\Framework\DB\Select;
use Magento\Newsletter\Model\ResourceModel\Subscriber\Collection as ExtendSubscriberCollection;

/**
 * Class Collection
 * @codeCoverageIgnore
 */
class Collection extends ExtendSubscriberCollection
{

    protected function _initSelect(): Collection
    {
        parent::_initSelect();

        $this->getSelect()->joinLeft(
            ['ac_contact' => $this->_resource->getTable(SchemaInterface::CONTACT_TABLE)],
            'ac_contact.email = main_table.subscriber_email',
            ['ac_contact.activecampaign_id']
        );

        return $this;
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
     * Exclude dead-lettered rows while keeping null (no AC row yet), pending and synced rows.
     *
     * @return Collection
     */
    public function addNotDeadLetteredFilter(): self
    {
        $this->getSelect()->where(
            'ac_contact.export_status != ? OR ac_contact.export_status IS NULL',
            FailureTrackableInterface::EXPORT_STATUS_FAILED
        );
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
