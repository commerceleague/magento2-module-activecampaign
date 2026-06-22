<?php
declare(strict_types=1);
/**
 * Copyright © André Flitsch. All rights reserved.
 * See license.md for license details.
 */

namespace CommerceLeague\ActiveCampaign\Test\Unit\Model\Tombstone;

use CommerceLeague\ActiveCampaign\Model\ActiveCampaign\Customer;
use CommerceLeague\ActiveCampaign\Model\ActiveCampaign\GuestCustomer;
use CommerceLeague\ActiveCampaign\Model\ResourceModel\ActiveCampaign\Customer\Collection as CustomerCollection;
use CommerceLeague\ActiveCampaign\Model\ResourceModel\ActiveCampaign\Customer\CollectionFactory as CustomerCollectionFactory;
use CommerceLeague\ActiveCampaign\Model\ResourceModel\ActiveCampaign\GuestCustomer\Collection as GuestCustomerCollection;
use CommerceLeague\ActiveCampaign\Model\ResourceModel\ActiveCampaign\GuestCustomer\CollectionFactory as GuestCustomerCollectionFactory;
use CommerceLeague\ActiveCampaign\Model\Tombstone\TombstoneReconciler;
use CommerceLeague\ActiveCampaign\Model\Tombstone\TombstoneRelinker;
use CommerceLeague\ActiveCampaign\Test\Unit\AbstractTestCase;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Customer\Api\Data\CustomerInterface as MagentoCustomerInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use PHPUnit\Framework\MockObject\MockObject;

class TombstoneReconcilerTest extends AbstractTestCase
{
    /**
     * @var MockObject|CustomerCollectionFactory
     */
    private $customerCollectionFactory;

    /**
     * @var MockObject|GuestCustomerCollectionFactory
     */
    private $guestCustomerCollectionFactory;

    /**
     * @var MockObject|CustomerCollection
     */
    private $customerCollection;

    /**
     * @var MockObject|GuestCustomerCollection
     */
    private $guestCustomerCollection;

    /**
     * @var MockObject|CustomerRepositoryInterface
     */
    private $customerRepository;

    /**
     * @var MockObject|TombstoneRelinker
     */
    private $relinker;

    /**
     * @var array<string, array<int, MockObject>>
     */
    private $items = [];

    /**
     * @var TombstoneReconciler
     */
    private $reconciler;

    protected function setUp(): void
    {
        $this->customerCollection = $this->createMock(CustomerCollection::class);
        $this->guestCustomerCollection = $this->createMock(GuestCustomerCollection::class);

        $this->customerCollectionFactory = $this->mockFactory(
            CustomerCollectionFactory::class,
            $this->customerCollection
        );
        $this->guestCustomerCollectionFactory = $this->mockFactory(
            GuestCustomerCollectionFactory::class,
            $this->guestCustomerCollection
        );

        foreach ([
            'customer' => $this->customerCollection,
            'guest'    => $this->guestCustomerCollection,
        ] as $key => $collection) {
            $collection->method('addFieldToFilter')->willReturnSelf();
            $collection->method('getItems')->willReturnCallback(
                fn () => $this->items[$key] ?? []
            );
        }

        $this->customerRepository = $this->createMock(CustomerRepositoryInterface::class);
        $this->relinker = $this->createMock(TombstoneRelinker::class);

        $this->reconciler = new TombstoneReconciler(
            $this->customerCollectionFactory,
            $this->guestCustomerCollectionFactory,
            $this->customerRepository,
            $this->relinker
        );
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

    private function createCustomerMapping(int $activeCampaignId, int $magentoCustomerId): MockObject
    {
        $model = $this->createMock(Customer::class);
        $model->method('getActiveCampaignId')->willReturn($activeCampaignId);
        $model->method('getMagentoCustomerId')->willReturn($magentoCustomerId);

        return $model;
    }

    private function createGuestMapping(int $activeCampaignId, int $entityId, string $email): MockObject
    {
        $model = $this->createMock(GuestCustomer::class);
        $model->method('getActiveCampaignId')->willReturn($activeCampaignId);
        $model->method('getId')->willReturn($entityId);
        $model->method('getEmail')->willReturn($email);

        return $model;
    }

    private function stubMagentoCustomer(int $magentoCustomerId, string $email): void
    {
        $magentoCustomer = $this->createMock(MagentoCustomerInterface::class);
        $magentoCustomer->method('getEmail')->willReturn($email);
        $this->customerRepository->method('getById')
            ->with($magentoCustomerId)
            ->willReturn($magentoCustomer);
    }

    public function testReconcileIteratesRegisteredAndGuestCandidatesWithCommit(): void
    {
        $this->stubMagentoCustomer(77, 'reg@example.com');
        $this->items['customer'] = [$this->createCustomerMapping(42, 77)];
        $this->items['guest']    = [$this->createGuestMapping(60, 99, 'guest@example.com')];

        $this->customerCollectionFactory->expects($this->once())->method('create');
        $this->guestCustomerCollectionFactory->expects($this->once())->method('create');

        $this->relinker->expects($this->exactly(2))
            ->method('relink')
            ->willReturnMap([
                [42, 'reg@example.com', '77', true, TombstoneRelinker::RESULT_RELINKED],
                [60, 'guest@example.com', 'guest-99', true, TombstoneRelinker::RESULT_RELINKED],
            ]);

        $tally = $this->reconciler->reconcile(true);

        $this->assertSame(2, $tally[TombstoneRelinker::RESULT_RELINKED]);
        $this->assertSame(0, $tally['unresolved']);
        $this->assertSame(0, $tally['error']);
    }

    public function testReconcilePassesCommitFalseThrough(): void
    {
        $this->items['guest'] = [$this->createGuestMapping(60, 99, 'guest@example.com')];

        $this->relinker->expects($this->once())
            ->method('relink')
            ->with(60, 'guest@example.com', 'guest-99', false)
            ->willReturn(TombstoneRelinker::RESULT_NO_LIVE_CONTACT);

        $tally = $this->reconciler->reconcile(false);

        $this->assertSame(1, $tally[TombstoneRelinker::RESULT_NO_LIVE_CONTACT]);
    }

    public function testReconcileTalliesEachResultConstant(): void
    {
        $this->items['guest'] = [
            $this->createGuestMapping(60, 99, 'a@example.com'),
            $this->createGuestMapping(61, 100, 'b@example.com'),
            $this->createGuestMapping(62, 101, 'c@example.com'),
        ];

        $this->relinker->method('relink')->willReturnOnConsecutiveCalls(
            TombstoneRelinker::RESULT_RELINKED,
            TombstoneRelinker::RESULT_SKIPPED_CONFLICT,
            TombstoneRelinker::RESULT_NO_LIVE_CONTACT
        );

        $tally = $this->reconciler->reconcile(true);

        $this->assertSame(1, $tally[TombstoneRelinker::RESULT_RELINKED]);
        $this->assertSame(1, $tally[TombstoneRelinker::RESULT_SKIPPED_CONFLICT]);
        $this->assertSame(1, $tally[TombstoneRelinker::RESULT_NO_LIVE_CONTACT]);
    }

    public function testReconcileCountsUnresolvedWhenMagentoCustomerDeleted(): void
    {
        $this->items['customer'] = [$this->createCustomerMapping(42, 77)];

        $this->customerRepository->method('getById')
            ->with(77)
            ->willThrowException(new NoSuchEntityException(__('No such entity')));

        $this->relinker->expects($this->never())->method('relink');

        $tally = $this->reconciler->reconcile(true);

        $this->assertSame(1, $tally['unresolved']);
    }

    public function testReconcileIsolatesPerCandidateErrorsAndContinues(): void
    {
        $this->items['guest'] = [
            $this->createGuestMapping(60, 99, 'a@example.com'),
            $this->createGuestMapping(61, 100, 'b@example.com'),
        ];

        $this->relinker->method('relink')->willReturnCallback(
            function (int $ecomCustomerId) {
                if ($ecomCustomerId === 60) {
                    throw new \RuntimeException('503 Service Unavailable');
                }
                return TombstoneRelinker::RESULT_RELINKED;
            }
        );

        $tally = $this->reconciler->reconcile(true);

        // The first throws (counted as error), the loop continues and the
        // second is relinked.
        $this->assertSame(1, $tally['error']);
        $this->assertSame(1, $tally[TombstoneRelinker::RESULT_RELINKED]);
    }

    public function testReconcileInvokesOnRecordCallbackPerCandidate(): void
    {
        $this->items['guest'] = [
            $this->createGuestMapping(60, 99, 'a@example.com'),
            $this->createGuestMapping(61, 100, 'b@example.com'),
        ];

        $this->relinker->method('relink')->willReturn(TombstoneRelinker::RESULT_RELINKED);

        $records = [];
        $this->reconciler->reconcile(true, function (array $record) use (&$records): void {
            $records[] = $record;
        });

        $this->assertCount(2, $records);
        $this->assertSame(60, $records[0]['ecomCustomerId']);
        $this->assertSame('guest', $records[0]['type']);
        $this->assertSame(TombstoneRelinker::RESULT_RELINKED, $records[0]['outcome']);
    }

    public function testReconcileFiltersByNonNullActiveCampaignId(): void
    {
        $this->customerCollection->expects($this->atLeastOnce())
            ->method('addFieldToFilter')
            ->with('activecampaign_id', ['notnull' => true])
            ->willReturnSelf();

        $this->reconciler->reconcile(true);
    }
}
