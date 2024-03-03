<?php
declare(strict_types=1);
/**
 */

namespace CommerceLeague\ActiveCampaign\Test\Unit\Console\Command;

use CommerceLeague\ActiveCampaign\Console\Command\ExportAbandonedCartCommand;
use CommerceLeague\ActiveCampaign\Helper\Config as ConfigHelper;
use CommerceLeague\ActiveCampaign\MessageQueue\Topics;
use CommerceLeague\ActiveCampaign\Model\ResourceModel\Quote\CollectionFactory as QuoteCollectionFactory;
use CommerceLeague\ActiveCampaign\Test\Unit\AbstractTestCase;
use CommerceLeague\ActiveCampaign\vendor\Model\ResourceModel\Quote\Collection as QuoteCollection;
use Magento\Framework\Console\Cli;
use Magento\Framework\MessageQueue\PublisherInterface;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\Console\Exception\RuntimeException;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Helper\ProgressBarFactory;
use Symfony\Component\Console\Tester\CommandTester;

class ExportAbandonedCartCommandTest extends AbstractTestCase
{

    /**
     * @var MockObject|ConfigHelper
     */
    protected $configHelper;

    /**
     * @var MockObject|QuoteCollectionFactory
     */
    protected $quoteCollectionFactory;

    /**
     * @var MockObject|QuoteCollection
     */
    protected $quoteCollection;

    /**
     * @var MockObject|ProgressBarFactory
     */
    protected $progressBarFactory;

    /**
     * @var MockObject|PublisherInterface
     */
    protected $publisher;

    /**
     * @var ExportAbandonedCartCommand
     */
    protected $exportAbandonedCartCommand;

    /**
     * @var CommandTester
     */
    protected $exportAbandonedCartCommandTester;

    protected function setUp()
    {
        $this->configHelper = $this->createMock(ConfigHelper::class);

        $this->quoteCollectionFactory = $this->getMockBuilder(QuoteCollectionFactory::class)
            ->disableOriginalConstructor()
            ->setMethods(['create'])
            ->getMock();

        $this->quoteCollection = $this->createMock(QuoteCollection::class);

        $this->quoteCollectionFactory->expects($this->any())
            ->method('create')
            ->willReturn($this->quoteCollection);

        $this->progressBarFactory = $this->getMockBuilder(ProgressBarFactory::class)
            ->disableOriginalConstructor()
            ->setMethods(['create'])
            ->getMock();

        $this->publisher = $this->createMock(PublisherInterface::class);

        $this->exportAbandonedCartCommand = new ExportAbandonedCartCommand(
            $this->configHelper,
            $this->quoteCollectionFactory,
            $this->progressBarFactory,
            $this->publisher
        );

        $this->exportAbandonedCartCommandTester = new CommandTester($this->exportAbandonedCartCommand);
    }

    public function testExecuteDisabled()
    {
        $this->configHelper->expects($this->once())
            ->method('isEnabled')
            ->willReturn(false);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Export disabled by system configuration');

        $this->exportAbandonedCartCommandTester->execute([]);
    }

    public function testExecuteAbandonedCartExportDisabled()
    {
        $this->configHelper->expects($this->once())
            ->method('isEnabled')
            ->willReturn(true);

        $this->configHelper->expects($this->once())
            ->method('isAbandonedCartExportEnabled')
            ->willReturn(false);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Export disabled by system configuration');

        $this->exportAbandonedCartCommandTester->execute([]);
    }

    public function testExecuteWithoutOptions()
    {
        $this->configHelper->expects($this->once())
            ->method('isEnabled')
            ->willReturn(true);

        $this->configHelper->expects($this->once())
            ->method('isAbandonedCartExportEnabled')
            ->willReturn(true);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Please provide at least one option');

        $this->exportAbandonedCartCommandTester->execute([]);
    }

    public function testExecuteWithQuoteIdAndOtherOptions()
    {
        $this->configHelper->expects($this->once())
            ->method('isEnabled')
            ->willReturn(true);

        $this->configHelper->expects($this->once())
            ->method('isAbandonedCartExportEnabled')
            ->willReturn(true);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('You cannot use --quote-id together with another option');

        $this->exportAbandonedCartCommandTester->execute(
            ['--quote-id' => 123, '--omitted' => true, '--all' => true]
        );
    }

    public function testExecuteWithAllAndOmittedOption()
    {
        $this->configHelper->expects($this->once())
            ->method('isEnabled')
            ->willReturn(true);

        $this->configHelper->expects($this->once())
            ->method('isAbandonedCartExportEnabled')
            ->willReturn(true);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('You cannot use --omitted and --all together');

        $this->exportAbandonedCartCommandTester->execute(
            ['--omitted' => true, '--all' => true]
        );
    }

    public function testExecuteWithoutQuoteIds()
    {
        $this->configHelper->expects($this->once())
            ->method('isEnabled')
            ->willReturn(true);

        $this->configHelper->expects($this->once())
            ->method('isAbandonedCartExportEnabled')
            ->willReturn(true);

        $this->quoteCollection->expects($this->once())
            ->method('getAllIds')
            ->willReturn([]);

        $this->exportAbandonedCartCommandTester->execute(
            ['--all' => true]
        );

        $this->assertContains(
            'No abandoned cart(s) found matching your criteria',
            $this->exportAbandonedCartCommandTester->getDisplay()
        );

        $this->assertEquals(
            Cli::RETURN_FAILURE,
            $this->exportAbandonedCartCommandTester->getStatusCode()
        );
    }

    public function testExecuteWithQuoteIdOption()
    {
        $quoteId = 123;

        $this->configHelper->expects($this->once())
            ->method('isEnabled')
            ->willReturn(true);

        $this->configHelper->expects($this->once())
            ->method('isAbandonedCartExportEnabled')
            ->willReturn(true);

        $this->quoteCollection->expects($this->once())
            ->method('addIdFilter')
            ->with($quoteId)
            ->willReturnSelf();

        $this->quoteCollection->expects($this->never())
            ->method('addOmittedFilter');

        $this->quoteCollection->expects($this->once())
            ->method('getAllIds')
            ->willReturn([$quoteId]);

        $progressBar = new ProgressBar(new TestOutput());

        $this->progressBarFactory->expects($this->once())
            ->method('create')
            ->willReturn($progressBar);

        $this->publisher->expects($this->once())
            ->method('publish')
            ->with(
                Topics::QUOTE_ABANDONED_CART_EXPORT,
                json_encode(['quote_id' => $quoteId])
            );

        $this->exportAbandonedCartCommandTester->execute(
            ['--quote-id' => $quoteId]
        );

        $this->assertContains(
            '1 abandoned cart(s) have been scheduled for export.',
            $this->exportAbandonedCartCommandTester->getDisplay()
        );

        $this->assertEquals(Cli::RETURN_SUCCESS, $this->exportAbandonedCartCommandTester->getStatusCode());
    }

    public function testExecuteWithOmittedOption()
    {
        $quoteIds = [123, 456];

        $this->configHelper->expects($this->once())
            ->method('isEnabled')
            ->willReturn(true);

        $this->configHelper->expects($this->once())
            ->method('isAbandonedCartExportEnabled')
            ->willReturn(true);

        $this->quoteCollection->expects($this->never())
            ->method('addIdFilter');

        $this->quoteCollection->expects($this->once())
            ->method('addOmittedFilter')
            ->willReturnSelf();

        $this->quoteCollection->expects($this->once())
            ->method('getAllIds')
            ->willReturn($quoteIds);

        $progressBar = new ProgressBar(new TestOutput());

        $this->progressBarFactory->expects($this->once())
            ->method('create')
            ->willReturn($progressBar);

        $this->publisher->expects($this->exactly(2))
            ->method('publish');

        $this->exportAbandonedCartCommandTester->execute(
            ['--omitted' => true]
        );

        $this->assertContains(
            '2 abandoned cart(s) have been scheduled for export.',
            $this->exportAbandonedCartCommandTester->getDisplay()
        );

        $this->assertEquals(Cli::RETURN_SUCCESS, $this->exportAbandonedCartCommandTester->getStatusCode());
    }

    public function testExecuteWithAllOption()
    {
        $quoteIds = [123, 456, 789];

        $this->configHelper->expects($this->once())
            ->method('isEnabled')
            ->willReturn(true);

        $this->configHelper->expects($this->once())
            ->method('isAbandonedCartExportEnabled')
            ->willReturn(true);

        $this->quoteCollection->expects($this->never())
            ->method('addIdFilter');

        $this->quoteCollection->expects($this->never())
            ->method('addOmittedFilter');

        $this->quoteCollection->expects($this->once())
            ->method('getAllIds')
            ->willReturn($quoteIds);

        $progressBar = new ProgressBar(new TestOutput());

        $this->progressBarFactory->expects($this->once())
            ->method('create')
            ->willReturn($progressBar);

        $this->publisher->expects($this->exactly(3))
            ->method('publish');

        $this->exportAbandonedCartCommandTester->execute(
            ['--all' => true]
        );

        $this->assertContains(
            '3 abandoned cart(s) have been scheduled for export.',
            $this->exportAbandonedCartCommandTester->getDisplay()
        );

        $this->assertEquals(Cli::RETURN_SUCCESS, $this->exportAbandonedCartCommandTester->getStatusCode());
    }
}
