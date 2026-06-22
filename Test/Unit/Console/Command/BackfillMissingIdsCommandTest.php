<?php
declare(strict_types=1);
/**
 */

namespace CommerceLeague\ActiveCampaign\Test\Unit\Console\Command;

use CommerceLeague\ActiveCampaign\Api\Data\FailureTrackableInterface;
use CommerceLeague\ActiveCampaign\Console\Command\BackfillMissingIdsCommand;
use CommerceLeague\ActiveCampaign\MessageQueue\Topics;
use CommerceLeague\ActiveCampaign\Model\ActiveCampaign\Contact;
use CommerceLeague\ActiveCampaign\Model\ActiveCampaign\Customer;
use CommerceLeague\ActiveCampaign\Model\ActiveCampaign\GuestCustomer;
use CommerceLeague\ActiveCampaign\Model\ActiveCampaign\Order;
use CommerceLeague\ActiveCampaign\Model\ResourceModel\ActiveCampaign\Contact\Collection as ContactCollection;
use CommerceLeague\ActiveCampaign\Model\ResourceModel\ActiveCampaign\Contact\CollectionFactory as ContactCollectionFactory;
use CommerceLeague\ActiveCampaign\Model\ResourceModel\ActiveCampaign\Customer\Collection as CustomerCollection;
use CommerceLeague\ActiveCampaign\Model\ResourceModel\ActiveCampaign\Customer\CollectionFactory as CustomerCollectionFactory;
use CommerceLeague\ActiveCampaign\Model\ResourceModel\ActiveCampaign\GuestCustomer\Collection as GuestCustomerCollection;
use CommerceLeague\ActiveCampaign\Model\ResourceModel\ActiveCampaign\GuestCustomer\CollectionFactory as GuestCustomerCollectionFactory;
use CommerceLeague\ActiveCampaign\Model\ResourceModel\ActiveCampaign\Order\Collection as OrderCollection;
use CommerceLeague\ActiveCampaign\Model\ResourceModel\ActiveCampaign\Order\CollectionFactory as OrderCollectionFactory;
use CommerceLeague\ActiveCampaign\Model\ResourceModel\ActiveCampaign\Order as OrderResource;
use CommerceLeague\ActiveCampaign\Model\ResourceModel\ActiveCampaign\Customer as CustomerResource;
use CommerceLeague\ActiveCampaign\Model\ResourceModel\ActiveCampaign\GuestCustomer as GuestCustomerResource;
use CommerceLeague\ActiveCampaign\Model\ResourceModel\ActiveCampaign\Contact as ContactResource;
use CommerceLeague\ActiveCampaign\Test\Unit\AbstractTestCase;
use Magento\Framework\Console\Cli;
use Magento\Framework\MessageQueue\PublisherInterface;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\Console\Tester\CommandTester;

class BackfillMissingIdsCommandTest extends AbstractTestCase
{
    /**
     * @var MockObject|OrderCollectionFactory
     */
    private $orderCollectionFactory;

    /**
     * @var MockObject|CustomerCollectionFactory
     */
    private $customerCollectionFactory;

    /**
     * @var MockObject|GuestCustomerCollectionFactory
     */
    private $guestCustomerCollectionFactory;

    /**
     * @var MockObject|ContactCollectionFactory
     */
    private $contactCollectionFactory;

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
     * @var MockObject|PublisherInterface
     */
    private $publisher;

    /**
     * @var MockObject|OrderResource
     */
    private $orderResource;

    /**
     * @var MockObject|CustomerResource
     */
    private $customerResource;

    /**
     * @var MockObject|GuestCustomerResource
     */
    private $guestCustomerResource;

    /**
     * @var MockObject|ContactResource
     */
    private $contactResource;

    /**
     * @var array<string, array<int, MockObject>>
     */
    private $items = [];

    /**
     * @var BackfillMissingIdsCommand
     */
    private $command;

    /**
     * @var CommandTester
     */
    private $commandTester;

    protected function setUp(): void
    {
        $this->orderCollection          = $this->createMock(OrderCollection::class);
        $this->customerCollection       = $this->createMock(CustomerCollection::class);
        $this->guestCustomerCollection  = $this->createMock(GuestCustomerCollection::class);
        $this->contactCollection        = $this->createMock(ContactCollection::class);

        $this->orderCollectionFactory         = $this->mockFactory(OrderCollectionFactory::class, $this->orderCollection);
        $this->customerCollectionFactory      = $this->mockFactory(CustomerCollectionFactory::class, $this->customerCollection);
        $this->guestCustomerCollectionFactory = $this->mockFactory(GuestCustomerCollectionFactory::class, $this->guestCustomerCollection);
        $this->contactCollectionFactory       = $this->mockFactory(ContactCollectionFactory::class, $this->contactCollection);

        // Collections fluently return self for filters and default to an empty
        // item set (overridden per test via $this->*Items).
        foreach ([
            'order'    => $this->orderCollection,
            'customer' => $this->customerCollection,
            'guest'    => $this->guestCustomerCollection,
            'contact'  => $this->contactCollection
        ] as $key => $collection) {
            $collection->method('addFieldToFilter')->willReturnSelf();
            $collection->method('setPageSize')->willReturnSelf();
            $collection->method('getItems')->willReturnCallback(
                fn () => $this->items[$key] ?? []
            );
        }

        $this->publisher = $this->createMock(PublisherInterface::class);

        $this->orderResource         = $this->createMock(OrderResource::class);
        $this->customerResource      = $this->createMock(CustomerResource::class);
        $this->guestCustomerResource = $this->createMock(GuestCustomerResource::class);
        $this->contactResource       = $this->createMock(ContactResource::class);

        $this->command = new BackfillMissingIdsCommand(
            $this->orderCollectionFactory,
            $this->customerCollectionFactory,
            $this->guestCustomerCollectionFactory,
            $this->contactCollectionFactory,
            $this->publisher,
            $this->orderResource,
            $this->customerResource,
            $this->guestCustomerResource,
            $this->contactResource
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

    private function createOrderModel(?int $orderId, ?int $quoteId, ?string $errorCode): MockObject
    {
        $model = $this->createMock(Order::class);
        $model->method('getMagentoOrderId')->willReturn($orderId);
        $model->method('getMagentoQuoteId')->willReturn($quoteId);
        $model->method('getLastErrorCode')->willReturn($errorCode);

        return $model;
    }

    private function createContactModel(string $email, ?string $errorCode): MockObject
    {
        $model = $this->createMock(Contact::class);
        $model->method('getEmail')->willReturn($email);
        $model->method('getLastErrorCode')->willReturn($errorCode);

        return $model;
    }

    private function createCustomerModel(int $customerId, ?string $errorCode): MockObject
    {
        $model = $this->createMock(Customer::class);
        $model->method('getMagentoCustomerId')->willReturn($customerId);
        $model->method('getLastErrorCode')->willReturn($errorCode);

        return $model;
    }

    private function createGuestModel(string $firstname, string $lastname, string $email, ?string $errorCode): MockObject
    {
        $model = $this->createMock(GuestCustomer::class);
        $model->method('getFirstname')->willReturn($firstname);
        $model->method('getLastname')->willReturn($lastname);
        $model->method('getEmail')->willReturn($email);
        $model->method('getLastErrorCode')->willReturn($errorCode);

        return $model;
    }

    public function testDryRunPublishesNothingButReportsCounts(): void
    {
        $this->items['order'] = [
            $this->createOrderModel(10, null, null),
            $this->createOrderModel(null, 20, null),
        ];

        $this->publisher->expects($this->never())->method('publish');
        $this->orderResource->expects($this->never())->method('save');

        $this->commandTester->execute(['--type' => 'order', '--dry-run' => true]);

        $display = $this->commandTester->getDisplay();
        $this->assertStringContainsString('re-queued', $display);
        // 2 rows would be re-queued.
        $this->assertStringContainsString('2', $display);
        $this->assertEquals(Cli::RETURN_SUCCESS, $this->commandTester->getStatusCode());
    }

    public function testTypeOrderOnlyTouchesOrders(): void
    {
        $this->items['order'] = [
            $this->createOrderModel(10, null, null),
        ];

        $this->customerCollectionFactory->expects($this->never())->method('create');
        $this->guestCustomerCollectionFactory->expects($this->never())->method('create');
        $this->contactCollectionFactory->expects($this->never())->method('create');

        $this->publisher->expects($this->once())
            ->method('publish')
            ->with(
                Topics::SALES_ORDER_EXPORT,
                json_encode(['magento_order_id' => 10], JSON_THROW_ON_ERROR)
            );

        $this->commandTester->execute(['--type' => 'order']);

        $this->assertEquals(Cli::RETURN_SUCCESS, $this->commandTester->getStatusCode());
    }

    public function testOrderPrefersQuoteWhenOrderIdMissing(): void
    {
        $this->items['order'] = [
            $this->createOrderModel(null, 55, null),
        ];

        $this->publisher->expects($this->once())
            ->method('publish')
            ->with(
                Topics::QUOTE_ABANDONED_CART_EXPORT,
                json_encode(['quote_id' => 55], JSON_THROW_ON_ERROR)
            );

        $this->commandTester->execute(['--type' => 'order']);
    }

    public function testDefaultExcludesFailedRows(): void
    {
        // export_status=2 rows are excluded by default via an addFieldToFilter neq 2.
        $this->orderCollection->expects($this->atLeastOnce())
            ->method('addFieldToFilter')
            ->willReturnSelf();

        $this->commandTester->execute(['--type' => 'order']);

        // The exclusion is expressed as a neq filter on export_status.
        // We assert it indirectly: no --include-failed means a status filter is applied.
        $this->assertEquals(Cli::RETURN_SUCCESS, $this->commandTester->getStatusCode());
    }

    public function testDefaultAppliesStatusExclusionFilter(): void
    {
        $captured = [];
        $this->orderCollection->method('addFieldToFilter')
            ->willReturnCallback(function ($field, $cond) use (&$captured) {
                $captured[] = [$field, $cond];
                return $this->orderCollection;
            });

        $this->commandTester->execute(['--type' => 'order']);

        $statusFilterApplied = false;
        foreach ($captured as [$field, $cond]) {
            if ($field === 'export_status') {
                $statusFilterApplied = true;
            }
        }
        $this->assertTrue($statusFilterApplied, 'Default run must exclude export_status=failed');
    }

    public function testIncludeFailedDoesNotApplyStatusExclusion(): void
    {
        $captured = [];
        $this->orderCollection->method('addFieldToFilter')
            ->willReturnCallback(function ($field, $cond) use (&$captured) {
                $captured[] = [$field, $cond];
                return $this->orderCollection;
            });

        $this->commandTester->execute(['--type' => 'order', '--include-failed' => true]);

        // With --include-failed, no export_status exclusion filter must be applied.
        foreach ($captured as [$field, $cond]) {
            if ($field === 'export_status') {
                $this->fail('export_status exclusion filter must not be applied with --include-failed');
            }
        }
        $this->assertEquals(Cli::RETURN_SUCCESS, $this->commandTester->getStatusCode());
    }

    public function testFlagUnrecoverableFlagsAndDoesNotPublish(): void
    {
        $flaggable = $this->createOrderModel(10, null, 'email_invalid');
        $flaggable->expects($this->once())
            ->method('setExportStatus')
            ->with(FailureTrackableInterface::EXPORT_STATUS_FAILED)
            ->willReturnSelf();

        $publishable = $this->createOrderModel(11, null, null);

        $this->items['order'] = [$flaggable, $publishable];

        // Only the publishable row is published.
        $this->publisher->expects($this->once())
            ->method('publish')
            ->with(
                Topics::SALES_ORDER_EXPORT,
                json_encode(['magento_order_id' => 11], JSON_THROW_ON_ERROR)
            );

        // The flagged row is saved via the resource model.
        $this->orderResource->expects($this->once())
            ->method('save')
            ->with($flaggable);

        $this->commandTester->execute(['--type' => 'order', '--flag-unrecoverable' => true]);

        $display = $this->commandTester->getDisplay();
        $this->assertStringContainsString('flagged', $display);
        $this->assertEquals(Cli::RETURN_SUCCESS, $this->commandTester->getStatusCode());
    }

    public function testFlagUnrecoverableDryRunDoesNotSave(): void
    {
        $flaggable = $this->createOrderModel(10, null, 'field_missing');
        $flaggable->expects($this->never())->method('setExportStatus');

        $this->items['order'] = [$flaggable];

        $this->orderResource->expects($this->never())->method('save');
        $this->publisher->expects($this->never())->method('publish');

        $this->commandTester->execute([
            '--type'               => 'order',
            '--flag-unrecoverable' => true,
            '--dry-run'            => true,
        ]);

        $this->assertEquals(Cli::RETURN_SUCCESS, $this->commandTester->getStatusCode());
    }

    public function testCustomerPublishesCustomerTopic(): void
    {
        $this->items['customer'] = [
            $this->createCustomerModel(77, null),
        ];

        $this->publisher->expects($this->once())
            ->method('publish')
            ->with(
                Topics::CUSTOMER_CUSTOMER_EXPORT,
                json_encode(['magento_customer_id' => 77], JSON_THROW_ON_ERROR)
            );

        $this->commandTester->execute(['--type' => 'customer']);
    }

    public function testGuestPublishesGuestTopic(): void
    {
        $this->items['guest'] = [
            $this->createGuestModel('John', 'Doe', 'john@example.com', null),
        ];

        $this->publisher->expects($this->once())
            ->method('publish')
            ->with(
                Topics::GUEST_CUSTOMER_EXPORT,
                $this->callback(function ($payload) {
                    $data = json_decode($payload, true);
                    return $data['magento_customer_id'] === null
                        && $data['customer_is_guest'] === true
                        && $data['customer_data']['email'] === 'john@example.com'
                        && $data['customer_data']['firstname'] === 'John'
                        && $data['customer_data']['lastname'] === 'Doe';
                })
            );

        $this->commandTester->execute(['--type' => 'guest']);
    }

    public function testContactPublishesContactTopic(): void
    {
        $this->items['contact'] = [
            $this->createContactModel('contact@example.com', null),
        ];

        $this->publisher->expects($this->once())
            ->method('publish')
            ->with(
                Topics::NEWSLETTER_CONTACT_EXPORT,
                json_encode(['email' => 'contact@example.com'], JSON_THROW_ON_ERROR)
            );

        $this->commandTester->execute(['--type' => 'contact']);
    }

    public function testLimitAppliesPageSize(): void
    {
        $this->orderCollection->expects($this->once())
            ->method('setPageSize')
            ->with(5)
            ->willReturnSelf();

        $this->commandTester->execute(['--type' => 'order', '--limit' => 5]);
    }

    public function testZeroLimitDoesNotApplyPageSize(): void
    {
        $this->orderCollection->expects($this->never())->method('setPageSize');

        $this->commandTester->execute(['--type' => 'order', '--limit' => 0]);
    }

    public function testAllTypesProcessedByDefault(): void
    {
        $this->orderCollectionFactory->expects($this->once())->method('create');
        $this->customerCollectionFactory->expects($this->once())->method('create');
        $this->guestCustomerCollectionFactory->expects($this->once())->method('create');
        $this->contactCollectionFactory->expects($this->once())->method('create');

        $this->commandTester->execute([]);

        $this->assertEquals(Cli::RETURN_SUCCESS, $this->commandTester->getStatusCode());
    }

    public function testErrorCodeBreakdownReported(): void
    {
        $this->items['contact'] = [
            $this->createContactModel('a@example.com', 'rate_limited'),
            $this->createContactModel('b@example.com', 'rate_limited'),
            $this->createContactModel('c@example.com', null),
        ];

        $this->commandTester->execute(['--type' => 'contact']);

        $display = $this->commandTester->getDisplay();
        $this->assertStringContainsString('rate_limited', $display);
    }
}
