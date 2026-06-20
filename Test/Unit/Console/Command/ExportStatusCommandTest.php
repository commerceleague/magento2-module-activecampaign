<?php
declare(strict_types=1);
/**
 */

namespace CommerceLeague\ActiveCampaign\Test\Unit\Console\Command;

use CommerceLeague\ActiveCampaign\Console\Command\ExportStatusCommand;
use CommerceLeague\ActiveCampaign\Model\ResourceModel\ActiveCampaign\Contact\Collection as ContactCollection;
use CommerceLeague\ActiveCampaign\Model\ResourceModel\ActiveCampaign\Contact\CollectionFactory as ContactCollectionFactory;
use CommerceLeague\ActiveCampaign\Model\ResourceModel\ActiveCampaign\Customer\Collection as CustomerCollection;
use CommerceLeague\ActiveCampaign\Model\ResourceModel\ActiveCampaign\Customer\CollectionFactory as CustomerCollectionFactory;
use CommerceLeague\ActiveCampaign\Model\ResourceModel\ActiveCampaign\GuestCustomer\Collection as GuestCustomerCollection;
use CommerceLeague\ActiveCampaign\Model\ResourceModel\ActiveCampaign\GuestCustomer\CollectionFactory as GuestCustomerCollectionFactory;
use CommerceLeague\ActiveCampaign\Model\ResourceModel\ActiveCampaign\Order\Collection as OrderCollection;
use CommerceLeague\ActiveCampaign\Model\ResourceModel\ActiveCampaign\Order\CollectionFactory as OrderCollectionFactory;
use CommerceLeague\ActiveCampaign\Test\Unit\AbstractTestCase;
use Magento\Framework\Console\Cli;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\Console\Tester\CommandTester;

class ExportStatusCommandTest extends AbstractTestCase
{
    /**
     * @var MockObject|OrderCollection
     */
    private $orderCollection;

    /**
     * @var MockObject|CustomerCollection
     */
    private $customerCollection;

    /**
     * @var MockObject|GuestCustomerCollection
     */
    private $guestCustomerCollection;

    /**
     * @var MockObject|ContactCollection
     */
    private $contactCollection;

    /**
     * @var ExportStatusCommand
     */
    private $command;

    /**
     * @var CommandTester
     */
    private $commandTester;

    protected function setUp(): void
    {
        $this->orderCollection         = $this->createMock(OrderCollection::class);
        $this->customerCollection      = $this->createMock(CustomerCollection::class);
        $this->guestCustomerCollection = $this->createMock(GuestCustomerCollection::class);
        $this->contactCollection       = $this->createMock(ContactCollection::class);

        $orderFactory   = $this->mockFactory(OrderCollectionFactory::class, $this->orderCollection);
        $customerFactory = $this->mockFactory(CustomerCollectionFactory::class, $this->customerCollection);
        $guestFactory   = $this->mockFactory(GuestCustomerCollectionFactory::class, $this->guestCustomerCollection);
        $contactFactory = $this->mockFactory(ContactCollectionFactory::class, $this->contactCollection);

        $this->command = new ExportStatusCommand(
            $orderFactory,
            $customerFactory,
            $guestFactory,
            $contactFactory
        );

        $this->commandTester = new CommandTester($this->command);
    }

    /**
     * @param class-string $factoryClass
     */
    private function mockFactory(string $factoryClass, MockObject $collection): MockObject
    {
        $factory = $this->getMockBuilder($factoryClass)
            ->disableOriginalConstructor()
            ->onlyMethods(['create'])
            ->getMock();
        $factory->method('create')->willReturn($collection);

        return $factory;
    }

    /**
     * Configures a collection mock: a fresh clone is created per query via create(),
     * but here a single collection instance answers all queries. We drive getSize()
     * sequentially: total, null-count, then per-status counts.
     *
     * @param int[]                 $sizes
     * @param array<string, int>    $errorBreakdown
     */
    private function configureCollection(MockObject $collection, array $sizes, array $errorBreakdown): void
    {
        $collection->method('addFieldToFilter')->willReturnSelf();
        $collection->method('getSize')->willReturnOnConsecutiveCalls(...$sizes);

        $items = [];
        foreach ($errorBreakdown as $code => $count) {
            for ($i = 0; $i < $count; $i++) {
                $model = $this->getMockBuilder(\stdClass::class)
                    ->addMethods(['getLastErrorCode'])
                    ->getMock();
                $model->method('getLastErrorCode')->willReturn($code);
                $items[] = $model;
            }
        }
        $collection->method('getItems')->willReturn($items);
    }

    public function testStatusReportsPerTableBreakdown(): void
    {
        // Each table: sizes = [total, null-count, pending, synced, failed]
        $this->configureCollection(
            $this->orderCollection,
            [100, 12, 80, 8, 12],
            ['email_invalid' => 5, 'rate_limited' => 7]
        );
        $this->configureCollection($this->customerCollection, [50, 3, 47, 0, 3], ['field_missing' => 3]);
        $this->configureCollection($this->guestCustomerCollection, [20, 1, 19, 0, 1], ['email_invalid' => 1]);
        $this->configureCollection($this->contactCollection, [200, 40, 160, 0, 40], ['rate_limited' => 40]);

        $this->commandTester->execute([]);

        $display = $this->commandTester->getDisplay();

        // Totals per table appear.
        $this->assertStringContainsString('100', $display);
        $this->assertStringContainsString('200', $display);
        // NULL counts appear.
        $this->assertStringContainsString('12', $display);
        $this->assertStringContainsString('40', $display);
        // Error-code breakdown appears.
        $this->assertStringContainsString('email_invalid', $display);
        $this->assertStringContainsString('rate_limited', $display);
        $this->assertStringContainsString('field_missing', $display);

        $this->assertEquals(Cli::RETURN_SUCCESS, $this->commandTester->getStatusCode());
    }

    public function testStatusDoesNotMutate(): void
    {
        foreach ([
            $this->orderCollection,
            $this->customerCollection,
            $this->guestCustomerCollection,
            $this->contactCollection
        ] as $collection) {
            $this->configureCollection($collection, [0, 0, 0, 0, 0], []);
            $collection->expects($this->never())->method('save');
        }

        $this->commandTester->execute([]);

        $this->assertEquals(Cli::RETURN_SUCCESS, $this->commandTester->getStatusCode());
    }
}
