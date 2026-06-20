<?php
declare(strict_types=1);

namespace CommerceLeague\ActiveCampaign\Model\ResourceModel\Customer;

use CommerceLeague\ActiveCampaign\Api\Data\FailureTrackableInterface;
use CommerceLeague\ActiveCampaign\Helper\Config as ConfigHelper;
use CommerceLeague\ActiveCampaign\Setup\SchemaInterface;
use Magento\Customer\Model\ResourceModel\Customer\Collection as ExtendCustomerCollection;

/**
 * Class Collection
 *
 * @codeCoverageIgnore
 */
class Collection extends ExtendCustomerCollection
{

    /**
     * Collection constructor.
     *
     * @param \Magento\Framework\Data\Collection\EntityFactory                  $entityFactory
     * @param \Psr\Log\LoggerInterface                                          $logger
     * @param \Magento\Framework\Data\Collection\Db\FetchStrategyInterface      $fetchStrategy
     * @param \Magento\Framework\Event\ManagerInterface                         $eventManager
     * @param \Magento\Eav\Model\Config                                         $eavConfig
     * @param \Magento\Framework\App\ResourceConnection                         $resource
     * @param \Magento\Eav\Model\EntityFactory                                  $eavEntityFactory
     * @param \Magento\Eav\Model\ResourceModel\Helper                           $resourceHelper
     * @param \Magento\Framework\Validator\UniversalFactory                     $universalFactory
     * @param \Magento\Framework\Model\ResourceModel\Db\VersionControl\Snapshot $entitySnapshot
     * @param \Magento\Framework\DataObject\Copy\Config                         $fieldsetConfig
     * @param ConfigHelper                                                      $configHelper
     * @param \Magento\Framework\DB\Adapter\AdapterInterface|null               $connection
     * @param string                                                            $modelName

     */
    public function __construct(
        \Magento\Framework\Data\Collection\EntityFactory $entityFactory,
        \Psr\Log\LoggerInterface $logger,
        \Magento\Framework\Data\Collection\Db\FetchStrategyInterface $fetchStrategy,
        \Magento\Framework\Event\ManagerInterface $eventManager,
        \Magento\Eav\Model\Config $eavConfig,
        \Magento\Framework\App\ResourceConnection $resource,
        \Magento\Eav\Model\EntityFactory $eavEntityFactory,
        \Magento\Eav\Model\ResourceModel\Helper $resourceHelper,
        \Magento\Framework\Validator\UniversalFactory $universalFactory,
        \Magento\Framework\Model\ResourceModel\Db\VersionControl\Snapshot $entitySnapshot,
        \Magento\Framework\DataObject\Copy\Config $fieldsetConfig,
        private readonly ConfigHelper $configHelper,
        \Magento\Framework\DB\Adapter\AdapterInterface $connection = null,
        $modelName = ExtendCustomerCollection::CUSTOMER_MODEL_NAME
    ) {
        parent::__construct(
            $entityFactory,
            $logger,
            $fetchStrategy,
            $eventManager,
            $eavConfig,
            $resource,
            $eavEntityFactory,
            $resourceHelper,
            $universalFactory,
            $entitySnapshot,
            $fieldsetConfig,
            $connection,
            $modelName
        );
    }

    /**
     * @return Collection
     */
    public function addEmailFilter(string $email): self
    {
        $this->getSelect()->where('e.email = ?', $email);
        return $this;
    }

    /**
     * @param bool $applyCustomerGroupFilter When false the allowed-customer-group window
     *                                        filter is skipped (used for retry-all-omitted).
     * @return Collection
     */
    public function addContactOmittedFilter(bool $applyCustomerGroupFilter = true): self
    {
        $this->getSelect()->where('ac_contact.activecampaign_id IS NULL');
        if ($applyCustomerGroupFilter) {
            $this->addAllowedCustomerGroupsFilter();
        }
        return $this;
    }

    /**
     * @param bool $applyCustomerGroupFilter When false the allowed-customer-group window
     *                                        filter is skipped (used for retry-all-omitted).
     * @return Collection
     */
    public function addCustomerOmittedFilter(bool $applyCustomerGroupFilter = true): self
    {
        $this->getSelect()->where('ac_customer.activecampaign_id IS NULL');
        if ($applyCustomerGroupFilter) {
            $this->addAllowedCustomerGroupsFilter();
        }
        return $this;
    }

    /**
     * Exclude dead-lettered contact rows while keeping null, pending and synced rows.
     *
     * @return Collection
     */
    public function addContactNotDeadLetteredFilter(): self
    {
        $this->getSelect()->where(
            'ac_contact.export_status != ? OR ac_contact.export_status IS NULL',
            FailureTrackableInterface::EXPORT_STATUS_FAILED
        );
        return $this;
    }

    /**
     * Exclude dead-lettered customer rows while keeping null, pending and synced rows.
     *
     * @return Collection
     */
    public function addCustomerNotDeadLetteredFilter(): self
    {
        $this->getSelect()->where(
            'ac_customer.export_status != ? OR ac_customer.export_status IS NULL',
            FailureTrackableInterface::EXPORT_STATUS_FAILED
        );
        return $this;
    }

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

    private function addAllowedCustomerGroupsFilter(): self
    {
        // get array of allowed customer groups
        $this->getSelect()->where('e.group_id IN (?)', $this->configHelper->getAllowedCustomerGroupIds());
        return $this;
    }
}
