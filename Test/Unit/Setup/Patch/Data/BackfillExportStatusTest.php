<?php

declare(strict_types=1);

namespace CommerceLeague\ActiveCampaign\Test\Unit\Setup\Patch\Data;

use CommerceLeague\ActiveCampaign\Api\Data\FailureTrackableInterface;
use CommerceLeague\ActiveCampaign\Setup\Patch\Data\BackfillExportStatus;
use CommerceLeague\ActiveCampaign\Setup\SchemaInterface;
use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class BackfillExportStatusTest extends TestCase
{
    /**
     * @var MockObject|ModuleDataSetupInterface
     */
    private $moduleDataSetup;

    /**
     * @var MockObject|AdapterInterface
     */
    private $connection;

    /**
     * @var BackfillExportStatus
     */
    private $patch;

    protected function setUp(): void
    {
        $this->moduleDataSetup = $this->createMock(ModuleDataSetupInterface::class);
        $this->connection      = $this->createMock(AdapterInterface::class);

        $this->moduleDataSetup->method('getConnection')->willReturn($this->connection);
        $this->moduleDataSetup->method('getTable')->willReturnArgument(0);

        $this->patch = new BackfillExportStatus($this->moduleDataSetup);
    }

    public function testApplySetsSyncedForEachMappingTableThatHasAnAcIdButIsStillPending(): void
    {
        $this->connection->expects($this->once())->method('startSetup')->willReturnSelf();
        $this->connection->expects($this->once())->method('endSetup')->willReturnSelf();

        $calls = [];
        $this->connection->expects($this->exactly(4))
            ->method('update')
            ->willReturnCallback(function (string $table, array $bind, array $where) use (&$calls): int {
                $calls[] = ['table' => $table, 'bind' => $bind, 'where' => $where];
                return 1;
            });

        $this->assertSame($this->patch, $this->patch->apply());

        $tables = array_column($calls, 'table');
        $this->assertSame(
            [
                SchemaInterface::CONTACT_TABLE,
                SchemaInterface::CUSTOMER_TABLE,
                SchemaInterface::ORDER_TABLE,
                SchemaInterface::GUEST_CUSTOMER_TABLE,
            ],
            $tables
        );

        foreach ($calls as $call) {
            $this->assertSame(
                ['export_status' => FailureTrackableInterface::EXPORT_STATUS_SYNCED],
                $call['bind'],
                'Each table must be set to the synced status'
            );
            // Idempotent guard: only rows with an AC id that are still at the pending default.
            $this->assertContains('activecampaign_id IS NOT NULL', $call['where']);
            $this->assertArrayHasKey('export_status = ?', $call['where']);
            $this->assertSame(
                FailureTrackableInterface::EXPORT_STATUS_PENDING,
                $call['where']['export_status = ?']
            );
        }
    }

    public function testHasNoDependenciesOrAliases(): void
    {
        $this->assertSame([], BackfillExportStatus::getDependencies());
        $this->assertSame([], $this->patch->getAliases());
    }
}
