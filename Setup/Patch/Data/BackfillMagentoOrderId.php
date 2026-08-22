<?php

declare(strict_types=1);

namespace CommerceLeague\ActiveCampaign\Setup\Patch\Data;

use CommerceLeague\ActiveCampaign\Setup\SchemaInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;
use Magento\Sales\Api\Data\OrderInterface as MagentoOrderInterface;

/**
 * Backfills `magento_order_id` for mapping rows exported before that column existed.
 *
 * The `magento_order_id` column was added in 2.0.0 with no value backfill, so every
 * order exported by a pre-2.0.0 version carries an `activecampaign_id` but a NULL
 * `magento_order_id`. The 2.1.0 omitted-order filter treats that NULL as "never
 * exported", so the first repair cron after upgrading re-publishes the entire
 * historical order book. This one-time patch resolves the Magento order id via the
 * quote id already stored on the mapping row and stamps it in.
 *
 * Rows stamped here include carts converted to orders before this release whose
 * order export never actually succeeded (the mapping row was created for the cart,
 * not the order). Stamping them anyway preserves the pre-2.1.0 status quo: they were
 * just as unrepairable before this release as they are after it, so this patch does
 * not newly expose them to the repair cron. Rows created by exports that run after
 * 2.1.0 are unaffected — they only ever go NULL -> real id via a successful export.
 */
class BackfillMagentoOrderId implements DataPatchInterface
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

        $orderTable = $this->moduleDataSetup->getTable(SchemaInterface::ORDER_TABLE);
        $salesOrderTable = $this->moduleDataSetup->getTable('sales_order');

        $select = $connection->select()
            ->from(false, ['magento_order_id' => sprintf('so.%s', MagentoOrderInterface::ENTITY_ID)])
            ->join(
                ['so' => $salesOrderTable],
                sprintf('so.%s = ac.magento_quote_id', MagentoOrderInterface::QUOTE_ID),
                []
            )
            ->where('ac.activecampaign_id IS NOT NULL')
            ->where('ac.magento_order_id IS NULL');

        $connection->query($connection->updateFromSelect($select, ['ac' => $orderTable]));

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
