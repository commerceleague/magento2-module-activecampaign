<?php
declare(strict_types=1);
/**
 * Copyright © André Flitsch. All rights reserved.
 * See license.md for license details.
 */

namespace CommerceLeague\ActiveCampaign\Test\Unit\Console\Command;

use CommerceLeague\ActiveCampaign\Console\Command\RelinkTombstonesCommand;
use CommerceLeague\ActiveCampaign\Helper\Config;
use CommerceLeague\ActiveCampaign\Model\ActiveCampaign\Customer;
use CommerceLeague\ActiveCampaign\Model\ActiveCampaign\GuestCustomer;
use CommerceLeague\ActiveCampaign\Model\ResourceModel\ActiveCampaign\Customer\Collection as CustomerCollection;
use CommerceLeague\ActiveCampaign\Model\ResourceModel\ActiveCampaign\Customer\CollectionFactory as CustomerCollectionFactory;
use CommerceLeague\ActiveCampaign\Model\ResourceModel\ActiveCampaign\GuestCustomer\Collection as GuestCustomerCollection;
use CommerceLeague\ActiveCampaign\Model\ResourceModel\ActiveCampaign\GuestCustomer\CollectionFactory as GuestCustomerCollectionFactory;
use CommerceLeague\ActiveCampaign\Model\Tombstone\TombstoneRelinker;
use CommerceLeague\ActiveCampaign\Test\Unit\AbstractTestCase;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Customer\Api\Data\CustomerInterface as MagentoCustomerInterface;
use Magento\Framework\Console\Cli;
use Magento\Framework\Exception\NoSuchEntityException;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\Console\Tester\CommandTester;

class RelinkTombstonesCommandTest extends AbstractTestCase
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
     * @var MockObject|Config
     */
    private $config;

    /**
     * @var MockObject|TombstoneRelinker
     */
    private $relinker;

    /**
     * @var array<string, array<int, MockObject>>
     */
    private $items = [];

    /**
     * @var RelinkTombstonesCommand
     */
    private $command;

    /**
     * @var CommandTester
     */
    private $commandTester;

    protected function setUp(): void
    {
        $this->customerCollection = $this->createMock(CustomerCollection::class);
        $this->guestCustomerCollection = $this->createMock(GuestCustomerCollection::class);
        // Dedicated, explicitly-qualified guest filter methods (V1/V2 fix).
        $this->guestCustomerCollection->method('addEntityIdFilter')->willReturnSelf();
        $this->guestCustomerCollection->method('addEmailFilter')->willReturnSelf();

        $this->customerCollectionFactory      = $this->mockFactory(
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
            $collection->method('setPageSize')->willReturnSelf();
            $collection->method('getItems')->willReturnCallback(
                fn () => $this->items[$key] ?? []
            );
        }

        $this->customerRepository = $this->createMock(CustomerRepositoryInterface::class);
        $this->config             = $this->createMock(Config::class);
        $this->relinker           = $this->createMock(TombstoneRelinker::class);

        $this->command = new RelinkTombstonesCommand(
            $this->customerCollectionFactory,
            $this->guestCustomerCollectionFactory,
            $this->customerRepository,
            $this->config,
            $this->relinker
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

    public function testDryRunByDefaultPassesCommitFalseAndSaysDryRun(): void
    {
        $this->stubMagentoCustomer(77, 'reg@example.com');
        $this->items['customer'] = [$this->createCustomerMapping(42, 77)];

        $this->relinker->expects($this->once())
            ->method('relink')
            ->with(42, 'reg@example.com', 77, false)
            ->willReturn(TombstoneRelinker::RESULT_RELINKED);

        $this->commandTester->execute(['--magento-customer-id' => 77]);

        $display = $this->commandTester->getDisplay();
        $this->assertStringContainsStringIgnoringCase('dry-run', $display);
        $this->assertStringContainsString('--commit', $display);
        $this->assertEquals(Cli::RETURN_SUCCESS, $this->commandTester->getStatusCode());
    }

    public function testCommitPassesCommitTrue(): void
    {
        $this->stubMagentoCustomer(77, 'reg@example.com');
        $this->items['customer'] = [$this->createCustomerMapping(42, 77)];

        $this->relinker->expects($this->once())
            ->method('relink')
            ->with(42, 'reg@example.com', 77, true)
            ->willReturn(TombstoneRelinker::RESULT_RELINKED);

        $this->commandTester->execute(['--magento-customer-id' => 77, '--commit' => true]);

        $this->assertEquals(Cli::RETURN_SUCCESS, $this->commandTester->getStatusCode());
    }

    public function testSingleMagentoCustomerIdTargetsExactlyOne(): void
    {
        $this->stubMagentoCustomer(77, 'reg@example.com');
        $this->items['customer'] = [
            $this->createCustomerMapping(42, 77),
            $this->createCustomerMapping(43, 88),
        ];

        // Only the matching mapping (magento_customer_id=77) is relinked.
        $this->relinker->expects($this->once())
            ->method('relink')
            ->with(42, 'reg@example.com', 77, false)
            ->willReturn(TombstoneRelinker::RESULT_RELINKED);

        // The guest collection is never touched for a single registered target.
        $this->guestCustomerCollectionFactory->expects($this->never())->method('create');

        $this->commandTester->execute(['--magento-customer-id' => 77]);

        $this->assertEquals(Cli::RETURN_SUCCESS, $this->commandTester->getStatusCode());
    }

    public function testEmailTargetsByEmailAcrossGuest(): void
    {
        $this->items['guest'] = [
            $this->createGuestMapping(60, 99, 'guest@example.com'),
            $this->createGuestMapping(61, 100, 'other@example.com'),
        ];

        $this->relinker->expects($this->once())
            ->method('relink')
            ->with(60, 'guest@example.com', 'guest-99', false)
            ->willReturn(TombstoneRelinker::RESULT_RELINKED);

        $this->commandTester->execute(['--email' => 'guest@example.com']);

        $this->assertEquals(Cli::RETURN_SUCCESS, $this->commandTester->getStatusCode());
    }

    public function testGuestIdTargetsSingleGuest(): void
    {
        $this->items['guest'] = [
            $this->createGuestMapping(60, 99, 'guest@example.com'),
        ];

        $this->relinker->expects($this->once())
            ->method('relink')
            ->with(60, 'guest@example.com', 'guest-99', false)
            ->willReturn(TombstoneRelinker::RESULT_NO_LIVE_CONTACT);

        $this->commandTester->execute(['--guest-id' => 99]);

        $this->assertEquals(Cli::RETURN_SUCCESS, $this->commandTester->getStatusCode());
    }

    public function testBatchAllIteratesBothCollections(): void
    {
        $this->stubMagentoCustomer(77, 'reg@example.com');
        $this->items['customer'] = [$this->createCustomerMapping(42, 77)];
        $this->items['guest']    = [$this->createGuestMapping(60, 99, 'guest@example.com')];

        $this->customerCollectionFactory->expects($this->once())->method('create');
        $this->guestCustomerCollectionFactory->expects($this->once())->method('create');

        $this->relinker->expects($this->exactly(2))
            ->method('relink')
            ->willReturn(TombstoneRelinker::RESULT_RELINKED);

        $this->commandTester->execute(['--all' => true]);

        $this->assertEquals(Cli::RETURN_SUCCESS, $this->commandTester->getStatusCode());
    }

    public function testSummaryReflectsMixedOutcomes(): void
    {
        $this->stubMagentoCustomer(77, 'reg@example.com');
        $this->items['customer'] = [
            $this->createCustomerMapping(42, 77),
        ];
        $this->items['guest'] = [
            $this->createGuestMapping(60, 99, 'a@example.com'),
            $this->createGuestMapping(61, 100, 'b@example.com'),
        ];

        $this->relinker->method('relink')->willReturnOnConsecutiveCalls(
            TombstoneRelinker::RESULT_RELINKED,
            TombstoneRelinker::RESULT_SKIPPED_CONFLICT,
            TombstoneRelinker::RESULT_NO_LIVE_CONTACT
        );

        $this->commandTester->execute(['--all' => true]);

        $display = $this->commandTester->getDisplay();
        $this->assertStringContainsString(TombstoneRelinker::RESULT_RELINKED, $display);
        $this->assertStringContainsString(TombstoneRelinker::RESULT_SKIPPED_CONFLICT, $display);
        $this->assertStringContainsString(TombstoneRelinker::RESULT_NO_LIVE_CONTACT, $display);
        $this->assertEquals(Cli::RETURN_SUCCESS, $this->commandTester->getStatusCode());
    }

    public function testRegisteredCustomerWithDeletedMagentoRecordIsSkipped(): void
    {
        $this->items['customer'] = [$this->createCustomerMapping(42, 77)];

        $this->customerRepository->method('getById')
            ->with(77)
            ->willThrowException(new NoSuchEntityException(__('No such entity')));

        // Cannot resolve the email -> the relinker must not be called for it.
        $this->relinker->expects($this->never())->method('relink');

        $this->commandTester->execute(['--magento-customer-id' => 77]);

        $this->assertEquals(Cli::RETURN_SUCCESS, $this->commandTester->getStatusCode());
    }

    public function testEmailTargetsSingleGuestAndReportsIt(): void
    {
        // V2: a single guest selected by --email must be processed, produce a
        // per-record line, and be tallied in the summary.
        $this->items['guest'] = [
            $this->createGuestMapping(60, 99, 'renate.ranegger@lep.ch'),
        ];

        $this->relinker->expects($this->once())
            ->method('relink')
            ->with(60, 'renate.ranegger@lep.ch', 'guest-99', false)
            ->willReturn(TombstoneRelinker::RESULT_RELINKED);

        $this->commandTester->execute(['--email' => 'renate.ranegger@lep.ch']);

        $display = $this->commandTester->getDisplay();
        $this->assertStringContainsString('ecomId=60', $display);
        $this->assertStringContainsString('type=' . 'guest', $display);
        $this->assertStringContainsString('email=renate.ranegger@lep.ch', $display);
        $this->assertStringContainsString('-> ' . TombstoneRelinker::RESULT_RELINKED, $display);
        // Summary tallies the relink.
        $this->assertMatchesRegularExpression('/relinked:\s*1/', $display);
        $this->assertEquals(Cli::RETURN_SUCCESS, $this->commandTester->getStatusCode());
    }

    public function testEmailMatchesGuestCaseInsensitively(): void
    {
        // V2 root cause: the stored guest email differs only in case from the
        // operator's input. The per-record narrowing used a case-SENSITIVE !==
        // which silently dropped the row. It must now match.
        $this->items['guest'] = [
            $this->createGuestMapping(60, 99, 'Renate.Ranegger@LEP.ch'),
        ];

        $this->relinker->expects($this->once())
            ->method('relink')
            ->with(60, 'Renate.Ranegger@LEP.ch', 'guest-99', false)
            ->willReturn(TombstoneRelinker::RESULT_RELINKED);

        $this->commandTester->execute(['--email' => 'renate.ranegger@lep.ch']);

        $display = $this->commandTester->getDisplay();
        $this->assertStringContainsString('ecomId=60', $display);
        $this->assertMatchesRegularExpression('/relinked:\s*1/', $display);
        $this->assertEquals(Cli::RETURN_SUCCESS, $this->commandTester->getStatusCode());
    }

    public function testEmailMatchesRegisteredCaseInsensitively(): void
    {
        // The registered real email (from the repository) differs only in case.
        $this->stubMagentoCustomer(77, 'Reg@Example.com');
        $this->items['customer'] = [$this->createCustomerMapping(42, 77)];

        $this->relinker->expects($this->once())
            ->method('relink')
            ->with(42, 'Reg@Example.com', 77, false)
            ->willReturn(TombstoneRelinker::RESULT_RELINKED);

        $this->commandTester->execute(['--email' => 'reg@example.com']);

        $display = $this->commandTester->getDisplay();
        $this->assertStringContainsString('ecomId=42', $display);
        $this->assertMatchesRegularExpression('/relinked:\s*1/', $display);
        $this->assertEquals(Cli::RETURN_SUCCESS, $this->commandTester->getStatusCode());
    }

    public function testSingleGuestDryRunPrintsPerRecordLineAndSummary(): void
    {
        // V3: single-target guest dry-run -> per-record line + summary, relinker
        // called with commit=false.
        $this->items['guest'] = [
            $this->createGuestMapping(60, 99, 'guest@example.com'),
        ];

        $this->relinker->expects($this->once())
            ->method('relink')
            ->with(60, 'guest@example.com', 'guest-99', false)
            ->willReturn(TombstoneRelinker::RESULT_RELINKED);

        $this->commandTester->execute(['--guest-id' => 99]);

        $display = $this->commandTester->getDisplay();
        $this->assertStringContainsString(
            'ecomId=60 type=guest email=guest@example.com -> ' . TombstoneRelinker::RESULT_RELINKED,
            $display
        );
        $this->assertStringContainsStringIgnoringCase('dry-run', $display);
        $this->assertStringContainsString('Summary', $display);
        $this->assertMatchesRegularExpression('/relinked:\s*1/', $display);
        $this->assertEquals(Cli::RETURN_SUCCESS, $this->commandTester->getStatusCode());
    }

    public function testSingleRegisteredDryRunPrintsPerRecordLineAndSummary(): void
    {
        // V3: single-target registered dry-run -> per-record line + summary.
        $this->stubMagentoCustomer(77, 'reg@example.com');
        $this->items['customer'] = [$this->createCustomerMapping(42, 77)];

        $this->relinker->expects($this->once())
            ->method('relink')
            ->with(42, 'reg@example.com', 77, false)
            ->willReturn(TombstoneRelinker::RESULT_RELINKED);

        $this->commandTester->execute(['--magento-customer-id' => 77]);

        $display = $this->commandTester->getDisplay();
        $this->assertStringContainsString(
            'ecomId=42 type=registered email=reg@example.com -> ' . TombstoneRelinker::RESULT_RELINKED,
            $display
        );
        $this->assertStringContainsStringIgnoringCase('dry-run', $display);
        $this->assertStringContainsString('Summary', $display);
        $this->assertMatchesRegularExpression('/relinked:\s*1/', $display);
        $this->assertEquals(Cli::RETURN_SUCCESS, $this->commandTester->getStatusCode());
    }

    public function testLimitAppliesPageSizeInBatch(): void
    {
        $this->customerCollection->expects($this->once())
            ->method('setPageSize')
            ->with(5)
            ->willReturnSelf();

        $this->commandTester->execute(['--all' => true, '--limit' => 5]);

        $this->assertEquals(Cli::RETURN_SUCCESS, $this->commandTester->getStatusCode());
    }
}
