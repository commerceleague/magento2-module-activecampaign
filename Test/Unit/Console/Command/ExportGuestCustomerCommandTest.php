<?php
declare(strict_types=1);
/**
 */

namespace CommerceLeague\ActiveCampaign\Test\Unit\Console\Command;

use CommerceLeague\ActiveCampaign\Api\Data\GuestCustomerInterface;
use CommerceLeague\ActiveCampaign\Console\Command\ExportGuestCustomerCommand;
use CommerceLeague\ActiveCampaign\Helper\Config as ConfigHelper;
use CommerceLeague\ActiveCampaign\MessageQueue\Topics;
use CommerceLeague\ActiveCampaign\Model\ResourceModel\GuestCustomer\CollectionFactory as CustomerCollectionFactory;
use CommerceLeague\ActiveCampaign\Test\Unit\AbstractTestCase;
use CommerceLeague\ActiveCampaign\Model\ResourceModel\GuestCustomer\Collection as CustomerCollection;
use Magento\Framework\Console\Cli;
use Magento\Framework\MessageQueue\PublisherInterface;
use Magento\Sales\Api\Data\OrderAddressInterface;
use Magento\Sales\Api\Data\OrderInterface;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\Console\Exception\RuntimeException;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Helper\ProgressBarFactory;
use Symfony\Component\Console\Tester\CommandTester;

class ExportGuestCustomerCommandTest extends AbstractTestCase
{

    /**
     * @var MockObject|ConfigHelper
     */
    protected $configHelper;

    /**
     * @var MockObject|CustomerCollectionFactory
     */
    protected $customerCollectionFactory;

    /**
     * @var MockObject|CustomerCollection
     */
    protected $customerCollection;

    /**
     * @var MockObject|ProgressBarFactory
     */
    protected $progressBarFactory;

    /**
     * @var MockObject|PublisherInterface
     */
    protected $publisher;

    /**
     * @var ExportGuestCustomerCommand
     */
    protected $exportGuestCustomerCommand;

    /**
     * @var CommandTester
     */
    protected $exportGuestCustomerCommandTester;

    protected function setUp(): void
    {
        $this->configHelper = $this->createMock(ConfigHelper::class);

        $this->customerCollectionFactory = $this->getMockBuilder(CustomerCollectionFactory::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['create'])
            ->getMock();

        $this->customerCollection = $this->createMock(CustomerCollection::class);

        $this->customerCollectionFactory->expects($this->any())
            ->method('create')
            ->willReturn($this->customerCollection);

        $this->publisher = $this->createMock(PublisherInterface::class);

        $this->progressBarFactory = $this->getMockBuilder(ProgressBarFactory::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['create'])
            ->getMock();

        $this->exportGuestCustomerCommand = new ExportGuestCustomerCommand(
            $this->configHelper,
            $this->customerCollectionFactory,
            $this->progressBarFactory,
            $this->publisher
        );

        $this->exportGuestCustomerCommandTester = new CommandTester($this->exportGuestCustomerCommand);
    }

    public function testExecuteDisabled()
    {
        $this->configHelper->expects($this->once())
            ->method('isEnabled')
            ->willReturn(false);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Export disabled by system configuration');

        $this->exportGuestCustomerCommandTester->execute([]);
    }

    public function testExecuteWithoutCustomers()
    {
        $this->configHelper->expects($this->once())
            ->method('isEnabled')
            ->willReturn(true);

        $this->configHelper->expects($this->once())
            ->method('isCustomerExportEnabled')
            ->willReturn(true);

        $this->customerCollection->expects($this->once())
            ->method('getItems')
            ->willReturn([]);

        $this->exportGuestCustomerCommandTester->execute(
            ['--all' => true]
        );

        $this->assertStringContainsString(
            'No customer(s) found matching your criteria',
            $this->exportGuestCustomerCommandTester->getDisplay()
        );

        $this->assertEquals(
            Cli::RETURN_FAILURE,
            $this->exportGuestCustomerCommandTester->getStatusCode()
        );
    }

    public function testExecuteFallsBackToBillingAddressWhenCustomerFirstnameIsNull()
    {
        $email = 'michaela@example.com';

        $this->configHelper->expects($this->once())
            ->method('isEnabled')
            ->willReturn(true);

        $this->configHelper->expects($this->once())
            ->method('isCustomerExportEnabled')
            ->willReturn(true);

        /** @var MockObject|OrderAddressInterface $billingAddress */
        $billingAddress = $this->createMock(OrderAddressInterface::class);
        $billingAddress->expects($this->atLeastOnce())
            ->method('getFirstname')
            ->willReturn('Michaela');
        $billingAddress->expects($this->atLeastOnce())
            ->method('getLastname')
            ->willReturn('Mustermann');

        /** @var MockObject|OrderInterface $order */
        $order = $this->createMock(OrderInterface::class);
        $order->expects($this->atLeastOnce())
            ->method('getCustomerFirstname')
            ->willReturn(null);
        $order->expects($this->atLeastOnce())
            ->method('getCustomerLastname')
            ->willReturn(null);
        $order->expects($this->atLeastOnce())
            ->method('getBillingAddress')
            ->willReturn($billingAddress);
        $order->expects($this->atLeastOnce())
            ->method('getCustomerEmail')
            ->willReturn($email);

        $this->customerCollection->expects($this->once())
            ->method('getItems')
            ->willReturn([$order]);

        $progressBar = new ProgressBar(new TestOutput());

        $this->progressBarFactory->expects($this->once())
            ->method('create')
            ->willReturn($progressBar);

        $this->publisher->expects($this->once())
            ->method('publish')
            ->with(
                Topics::GUEST_CUSTOMER_EXPORT,
                json_encode(
                    [
                        'magento_customer_id' => null,
                        'customer_is_guest'   => true,
                        'customer_data'       => [
                            GuestCustomerInterface::FIRSTNAME => 'Michaela',
                            GuestCustomerInterface::LASTNAME  => 'Mustermann',
                            GuestCustomerInterface::EMAIL     => $email
                        ]
                    ]
                )
            );

        $this->exportGuestCustomerCommandTester->execute(
            ['--all' => true]
        );

        $this->assertEquals(
            Cli::RETURN_SUCCESS,
            $this->exportGuestCustomerCommandTester->getStatusCode()
        );
    }

    public function testExecuteKeepsOrderFirstnameWhenPresent()
    {
        $email = 'john@example.com';

        $this->configHelper->expects($this->once())
            ->method('isEnabled')
            ->willReturn(true);

        $this->configHelper->expects($this->once())
            ->method('isCustomerExportEnabled')
            ->willReturn(true);

        /** @var MockObject|OrderInterface $order */
        $order = $this->createMock(OrderInterface::class);
        $order->expects($this->atLeastOnce())
            ->method('getCustomerFirstname')
            ->willReturn('John');
        $order->expects($this->atLeastOnce())
            ->method('getCustomerLastname')
            ->willReturn('Doe');
        $order->expects($this->never())
            ->method('getBillingAddress');
        $order->expects($this->atLeastOnce())
            ->method('getCustomerEmail')
            ->willReturn($email);

        $this->customerCollection->expects($this->once())
            ->method('getItems')
            ->willReturn([$order]);

        $progressBar = new ProgressBar(new TestOutput());

        $this->progressBarFactory->expects($this->once())
            ->method('create')
            ->willReturn($progressBar);

        $this->publisher->expects($this->once())
            ->method('publish')
            ->with(
                Topics::GUEST_CUSTOMER_EXPORT,
                json_encode(
                    [
                        'magento_customer_id' => null,
                        'customer_is_guest'   => true,
                        'customer_data'       => [
                            GuestCustomerInterface::FIRSTNAME => 'John',
                            GuestCustomerInterface::LASTNAME  => 'Doe',
                            GuestCustomerInterface::EMAIL     => $email
                        ]
                    ]
                )
            );

        $this->exportGuestCustomerCommandTester->execute(
            ['--all' => true]
        );

        $this->assertEquals(
            Cli::RETURN_SUCCESS,
            $this->exportGuestCustomerCommandTester->getStatusCode()
        );
    }
}
