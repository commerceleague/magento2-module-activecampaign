<?php
declare(strict_types=1);
/**
 */

namespace CommerceLeague\ActiveCampaign\Test\Unit\Console\Command;

use CommerceLeague\ActiveCampaign\Console\Command\ExportOrderCommand;
use CommerceLeague\ActiveCampaign\Helper\Config as ConfigHelper;
use CommerceLeague\ActiveCampaign\MessageQueue\Topics;
use CommerceLeague\ActiveCampaign\Model\ResourceModel\Order\CollectionFactory as OrderCollectionFactory;
use CommerceLeague\ActiveCampaign\Test\Unit\AbstractTestCase;
use CommerceLeague\ActiveCampaign\vendor\Model\ResourceModel\Order\Collection as OrderCollection;
use Magento\Framework\Console\Cli;
use Magento\Framework\MessageQueue\PublisherInterface;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\Console\Exception\RuntimeException;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Helper\ProgressBarFactory;
use Symfony\Component\Console\Tester\CommandTester;

class ExportOrderCommandTest extends AbstractTestCase
{

    /**
     * @var MockObject|ConfigHelper
     */
    protected $configHelper;

    /**
     * @var MockObject|OrderCollectionFactory
     */
    protected $orderCollectionFactory;

    /**
     * @var MockObject|OrderCollection
     */
    protected $orderCollection;

    /**
     * @var MockObject|ProgressBarFactory
     */
    protected $progressBarFactory;

    /**
     * @var MockObject|PublisherInterface
     */
    protected $publisher;

    /**
     * @var ExportOrderCommand
     */
    protected $exportOrderCommand;

    /**
     * @var CommandTester
     */
    protected $exportOrderCommandTester;

    protected function setUp()
    {
        $this->configHelper = $this->createMock(ConfigHelper::class);

        $this->orderCollectionFactory = $this->getMockBuilder(OrderCollectionFactory::class)
            ->disableOriginalConstructor()
            ->setMethods(['create'])
            ->getMock();

        $this->orderCollection = $this->createMock(OrderCollection::class);

        $this->orderCollectionFactory->expects($this->any())
            ->method('create')
            ->willReturn($this->orderCollection);

        $this->progressBarFactory = $this->getMockBuilder(ProgressBarFactory::class)
            ->disableOriginalConstructor()
            ->setMethods(['create'])
            ->getMock();

        $this->publisher = $this->createMock(PublisherInterface::class);

        $this->exportOrderCommand = new ExportOrderCommand(
            $this->configHelper,
            $this->orderCollectionFactory,
            $this->progressBarFactory,
            $this->publisher
        );

        $this->exportOrderCommandTester = new CommandTester($this->exportOrderCommand);
    }

    public function testExecuteDisabled()
    {
        $this->configHelper->expects($this->once())
            ->method('isEnabled')
            ->willReturn(false);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Export disabled by system configuration');

        $this->exportOrderCommandTester->execute([]);
    }

    public function testExecuteOrderExportDisabled()
    {
        $this->configHelper->expects($this->once())
            ->method('isEnabled')
            ->willReturn(true);

        $this->configHelper->expects($this->once())
            ->method('isOrderExportEnabled')
            ->willReturn(false);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Export disabled by system configuration');

        $this->exportOrderCommandTester->execute([]);
    }

    public function testExecuteWithoutOptions()
    {
        $this->configHelper->expects($this->once())
            ->method('isEnabled')
            ->willReturn(true);

        $this->configHelper->expects($this->once())
            ->method('isOrderExportEnabled')
            ->willReturn(true);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Please provide at least one option');

        $this->exportOrderCommandTester->execute([]);
    }

    public function testExecuteWithOrderIdAndOtherOptions()
    {
        $this->configHelper->expects($this->once())
            ->method('isEnabled')
            ->willReturn(true);

        $this->configHelper->expects($this->once())
            ->method('isOrderExportEnabled')
            ->willReturn(true);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('You cannot use --order-id together with another option');

        $this->exportOrderCommandTester->execute(
            ['--order-id' => 123, '--omitted' => true, '--all' => true]
        );
    }

    public function testExecuteWithAllAndOmittedOption()
    {
        $this->configHelper->expects($this->once())
            ->method('isEnabled')
            ->willReturn(true);

        $this->configHelper->expects($this->once())
            ->method('isOrderExportEnabled')
            ->willReturn(true);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('You cannot use --omitted and --all together');

        $this->exportOrderCommandTester->execute(
            ['--omitted' => true, '--all' => true]
        );
    }

    public function testExecuteWithoutOrderIds()
    {
        $this->configHelper->expects($this->once())
            ->method('isEnabled')
            ->willReturn(true);

        $this->configHelper->expects($this->once())
            ->method('isOrderExportEnabled')
            ->willReturn(true);

        $this->orderCollection->expects($this->once())
            ->method('getAllIds')
            ->willReturn([]);

        $this->exportOrderCommandTester->execute(
            ['--all' => true]
        );

        $this->assertContains(
            'No order(s) found matching your criteria',
            $this->exportOrderCommandTester->getDisplay()
        );

        $this->assertEquals(
            Cli::RETURN_FAILURE,
            $this->exportOrderCommandTester->getStatusCode()
        );
    }

    public function testExecuteWithOrderIdOption()
    {
        $orderId = 123;

        $this->configHelper->expects($this->once())
            ->method('isEnabled')
            ->willReturn(true);

        $this->configHelper->expects($this->once())
            ->method('isOrderExportEnabled')
            ->willReturn(true);

        $this->orderCollection->expects($this->once())
            ->method('addIdFilter')
            ->with($orderId)
            ->willReturnSelf();

        $this->orderCollection->expects($this->never())
            ->method('addOmittedFilter');

        $this->orderCollection->expects($this->once())
            ->method('getAllIds')
            ->willReturn([$orderId]);

        $progressBar = new ProgressBar(new TestOutput());

        $this->progressBarFactory->expects($this->once())
            ->method('create')
            ->willReturn($progressBar);

        $this->publisher->expects($this->once())
            ->method('publish')
            ->with(
                Topics::SALES_ORDER_EXPORT,
                json_encode(['magento_order_id' => $orderId])
            );

        $this->exportOrderCommandTester->execute(
            ['--order-id' => $orderId]
        );

        $this->assertContains(
            '1 order(s) have been scheduled for export.',
            $this->exportOrderCommandTester->getDisplay()
        );

        $this->assertEquals(Cli::RETURN_SUCCESS, $this->exportOrderCommandTester->getStatusCode());
    }

    public function testWithOmittedOption()
    {
        $orderIds = [123, 456];

        $this->configHelper->expects($this->once())
            ->method('isEnabled')
            ->willReturn(true);

        $this->configHelper->expects($this->once())
            ->method('isOrderExportEnabled')
            ->willReturn(true);

        $this->orderCollection->expects($this->never())
            ->method('addIdFilter');

        $this->orderCollection->expects($this->once())
            ->method('addOmittedFilter')
            ->willReturnSelf();

        $this->orderCollection->expects($this->once())
            ->method('getAllIds')
            ->willReturn($orderIds);

        $progressBar = new ProgressBar(new TestOutput());

        $this->progressBarFactory->expects($this->once())
            ->method('create')
            ->willReturn($progressBar);

        $this->publisher->expects($this->exactly(2))
            ->method('publish');

        $this->exportOrderCommandTester->execute(
            ['--omitted' => true]
        );

        $this->assertContains(
            '2 order(s) have been scheduled for export.',
            $this->exportOrderCommandTester->getDisplay()
        );

        $this->assertEquals(Cli::RETURN_SUCCESS, $this->exportOrderCommandTester->getStatusCode());
    }

    public function testExecuteWithAllOption()
    {
        $orderIds = [123, 456, 789];

        $this->configHelper->expects($this->once())
            ->method('isEnabled')
            ->willReturn(true);

        $this->configHelper->expects($this->once())
            ->method('isOrderExportEnabled')
            ->willReturn(true);


        $this->orderCollection->expects($this->never())
            ->method('addIdFilter');

        $this->orderCollection->expects($this->never())
            ->method('addOmittedFilter');

        $this->orderCollection->expects($this->once())
            ->method('getAllIds')
            ->willReturn($orderIds);

        $progressBar = new ProgressBar(new TestOutput());

        $this->progressBarFactory->expects($this->once())
            ->method('create')
            ->willReturn($progressBar);

        $this->publisher->expects($this->exactly(3))
            ->method('publish');

        $this->exportOrderCommandTester->execute(
            ['--all' => true]
        );

        $this->assertContains(
            '3 order(s) have been scheduled for export.',
            $this->exportOrderCommandTester->getDisplay()
        );

        $this->assertEquals(Cli::RETURN_SUCCESS, $this->exportOrderCommandTester->getStatusCode());
    }
}
