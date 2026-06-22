<?php
declare(strict_types=1);
/**
 * Copyright © André Flitsch. All rights reserved.
 * See license.md for license details.
 */

namespace CommerceLeague\ActiveCampaign\Test\Unit\Model\ResourceModel\ActiveCampaign\GuestCustomer;

use CommerceLeague\ActiveCampaign\Model\ResourceModel\ActiveCampaign\GuestCustomer\Collection;
use CommerceLeague\ActiveCampaign\Test\Unit\AbstractTestCase;
use Magento\Framework\DB\Adapter\Pdo\Mysql;
use Magento\Framework\DB\Select;
use Magento\Framework\DB\Select\SelectRenderer;

/**
 * SQL-asserting tests for the guest mapping collection.
 *
 * These tests exercise the real {@see Select} produced by the dedicated filter
 * methods (no live DB) so that an UNqualified column — which is ambiguous once
 * sales_order is LEFT JOINed in _initSelect() — is caught. A behavioural mock of
 * the command can never catch this because the SQL is never executed there.
 */
class CollectionTest extends AbstractTestCase
{
    /**
     * Build a real Select on a mocked adapter and a Collection partial-mock
     * whose getSelect() returns it, so the filter methods operate on a real
     * Select without needing _initSelect() or a live connection.
     *
     * @return array{0:Collection,1:Select}
     */
    private function buildCollectionWithSelect(): array
    {
        /** @var Mysql|\PHPUnit\Framework\MockObject\MockObject $adapter */
        $adapter = $this->getMockBuilder(Mysql::class)
            ->disableOriginalConstructor()
            ->getMock();

        // where('main_table.entity_id = ?', 123) routes the int value through
        // quoteInto; record the resolved condition string verbatim.
        $adapter->method('quoteInto')->willReturnCallback(
            static function ($text, $value) {
                return str_replace('?', (string)$value, (string)$text);
            }
        );

        $selectRenderer = new SelectRenderer([]);
        $select = new Select($adapter, $selectRenderer);

        /** @var Collection|\PHPUnit\Framework\MockObject\MockObject $collection */
        $collection = $this->getMockBuilder(Collection::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getSelect'])
            ->getMock();
        $collection->method('getSelect')->willReturn($select);

        return [$collection, $select];
    }

    public function testAddEntityIdFilterQualifiesWithMainTable(): void
    {
        [$collection, $select] = $this->buildCollectionWithSelect();

        $collection->addEntityIdFilter(123);

        $where = implode(' ', $select->getPart(Select::WHERE));

        $this->assertStringContainsString('main_table', $where);
        $this->assertStringContainsString('entity_id', $where);
        $this->assertStringContainsString('123', $where);
        // The bug: a bare, unqualified `entity_id =` in the WHERE is ambiguous.
        $this->assertDoesNotMatchRegularExpression(
            '/(?<![.\w])entity_id\s*=/',
            $where,
            'entity_id must be qualified as main_table.entity_id to avoid ambiguity'
        );
    }

    public function testAddEmailFilterQualifiesWithMainTable(): void
    {
        [$collection, $select] = $this->buildCollectionWithSelect();

        $collection->addEmailFilter('renate.ranegger@lep.ch');

        $where = implode(' ', $select->getPart(Select::WHERE));

        $this->assertStringContainsString('main_table', $where);
        $this->assertStringContainsString('email', $where);
        $this->assertStringContainsString('renate.ranegger@lep.ch', $where);
        // A bare `email =` is unsafe given sales_order.customer_email in the join.
        $this->assertDoesNotMatchRegularExpression(
            '/(?<![.\w])email\s*=/',
            $where,
            'email must be qualified as main_table.email'
        );
    }
}
