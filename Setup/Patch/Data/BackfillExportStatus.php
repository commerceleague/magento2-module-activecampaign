<?php

declare(strict_types=1);

namespace CommerceLeague\ActiveCampaign\Setup\Patch\Data;

use CommerceLeague\ActiveCampaign\Api\Data\FailureTrackableInterface;
use CommerceLeague\ActiveCampaign\Setup\SchemaInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;

/**
 * Marks pre-existing, already-synced mapping rows as synced.
 *
 * The `export_status` failure-tracking column was added with a default of 0
 * (pending). Rows that already carried an `activecampaign_id` before the column
 * existed are in fact synced, so this one-time patch sets `export_status` to
 * synced for every mapping row that has an `activecampaign_id` but is still at the
 * pending default, across all four mapping tables. Idempotent: it only touches
 * rows still at pending, so it never overwrites a synced/failed status set since.
 */
class BackfillExportStatus implements DataPatchInterface
{
    public function __construct(private readonly ModuleDataSetupInterface $moduleDataSetup)
    {
    }

    /**
     * @inheritDoc
     */
    public function apply(): self
    {
        $connection = $this->moduleDataSetup->getConnection();
        $connection->startSetup();

        $tables = [
            SchemaInterface::CONTACT_TABLE,
            SchemaInterface::CUSTOMER_TABLE,
            SchemaInterface::ORDER_TABLE,
            SchemaInterface::GUEST_CUSTOMER_TABLE,
        ];

        foreach ($tables as $table) {
            $connection->update(
                $this->moduleDataSetup->getTable($table),
                ['export_status' => FailureTrackableInterface::EXPORT_STATUS_SYNCED],
                [
                    'activecampaign_id IS NOT NULL',
                    'export_status = ?' => FailureTrackableInterface::EXPORT_STATUS_PENDING,
                ]
            );
        }

        $connection->endSetup();

        return $this;
    }

    /**
     * @inheritDoc
     *
     * @return array<int, string>
     */
    public static function getDependencies(): array
    {
        return [];
    }

    /**
     * @inheritDoc
     *
     * @return array<int, string>
     */
    public function getAliases(): array
    {
        return [];
    }
}
