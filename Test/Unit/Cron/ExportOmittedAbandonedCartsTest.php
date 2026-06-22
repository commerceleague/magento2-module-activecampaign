<?php
declare(strict_types=1);
/**
 */

namespace CommerceLeague\ActiveCampaign\Test\Unit\Cron;

use CommerceLeague\ActiveCampaign\Cron\PublishOmittedAbandonedCarts;
use CommerceLeague\ActiveCampaign\Helper\Config as ConfigHelper;
use CommerceLeague\ActiveCampaign\MessageQueue\Topics;
use CommerceLeague\ActiveCampaign\Model\ResourceModel\Quote\CollectionFactory as QuoteCollectionFactory;
use CommerceLeague\ActiveCampaign\Test\Unit\AbstractTestCase;
use CommerceLeague\ActiveCampaign\Model\ResourceModel\Quote\Collection as QuoteCollection;
use Magento\Framework\MessageQueue\PublisherInterface;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;

class ExportOmittedAbandonedCartsTest extends AbstractTestCase
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
     * @var MockObject|PublisherInterface
     */
    protected $publisher;

    /**
     * @var MockObject|LoggerInterface
     */
    protected $logger;

    /**
     * @var PublishOmittedAbandonedCarts
     */
    protected $exportOmittedAbandonedCarts;

    protected function setUp(): void
    {
        $this->configHelper = $this->createMock(ConfigHelper::class);
        $this->logger = $this->createMock(LoggerInterface::class);

        $this->quoteCollectionFactory = $this->getMockBuilder(QuoteCollectionFactory::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['create'])
            ->getMock();

        $this->quoteCollection = $this->createMock(QuoteCollection::class);

        $this->quoteCollectionFactory->expects($this->any())
            ->method('create')
            ->willReturn($this->quoteCollection);

        $this->publisher = $this->createMock(PublisherInterface::class);

        $this->exportOmittedAbandonedCarts = new PublishOmittedAbandonedCarts(
            $this->configHelper,
            $this->quoteCollectionFactory,
            $this->publisher,
            $this->logger
        );
    }

    public function testExecuteDisabled()
    {
        $this->configHelper->expects($this->once())
            ->method('isEnabled')
            ->willReturn(false);

        $this->quoteCollection->expects($this->never())
            ->method('addAbandonedFilter');

        $this->exportOmittedAbandonedCarts->run();
    }

    public function testExecuteAbandonedCartExportDisabled()
    {
        $this->configHelper->expects($this->once())
            ->method('isEnabled')
            ->willReturn(true);

        $this->configHelper->expects($this->once())
            ->method('isAbandonedCartExportEnabled')
            ->willReturn(false);

        $this->quoteCollection->expects($this->never())
            ->method('addAbandonedFilter');

        $this->exportOmittedAbandonedCarts->run();
    }

    public function testRun()
    {
        $quoteIds = [123, 456];

        $this->configHelper->expects($this->once())
            ->method('isEnabled')
            ->willReturn(true);

        $this->configHelper->expects($this->once())
            ->method('isAbandonedCartExportEnabled')
            ->willReturn(true);

        $this->configHelper->expects($this->exactly(2))
            ->method('isRetryAllOmittedEnabled')
            ->willReturn(false);

        $this->logger->expects($this->never())
            ->method('warning');

        $this->quoteCollection->expects($this->once())
            ->method('addAbandonedFilter')
            ->willReturnSelf();

        $this->quoteCollection->expects($this->once())
            ->method('addOmittedFilter')
            ->willReturnSelf();

        $this->quoteCollection->expects($this->once())
            ->method('addNotDeadLetteredFilter')
            ->willReturnSelf();

        $this->quoteCollection->expects($this->once())
            ->method('getAllIds')
            ->willReturn($quoteIds);

        $expectedPublishArguments = [
            [Topics::QUOTE_ABANDONED_CART_EXPORT, json_encode(['quote_id' => $quoteIds[0]])],
            [Topics::QUOTE_ABANDONED_CART_EXPORT, json_encode(['quote_id' => $quoteIds[1]])],
        ];
        $actualPublishArguments = [];
        $this->publisher->expects($this->exactly(2))
            ->method('publish')
            ->willReturnCallback(function (...$args) use (&$actualPublishArguments) {
                $actualPublishArguments[] = $args;
            });

        $this->exportOmittedAbandonedCarts->run();

        $this->assertSame($expectedPublishArguments, $actualPublishArguments);
    }

    public function testRunWithRetryAllOmittedSkipsWindowFilters()
    {
        $quoteIds = [123, 456];

        $this->configHelper->expects($this->once())
            ->method('isEnabled')
            ->willReturn(true);

        $this->configHelper->expects($this->once())
            ->method('isAbandonedCartExportEnabled')
            ->willReturn(true);

        $this->configHelper->expects($this->exactly(2))
            ->method('isRetryAllOmittedEnabled')
            ->willReturn(true);

        $this->logger->expects($this->once())
            ->method('warning')
            ->with($this->stringContains('retry_all_omitted is ON'));

        $this->quoteCollection->expects($this->never())
            ->method('addAbandonedFilter');

        $this->quoteCollection->expects($this->once())
            ->method('addOmittedFilter')
            ->willReturnSelf();

        $this->quoteCollection->expects($this->once())
            ->method('addNotDeadLetteredFilter')
            ->willReturnSelf();

        $this->quoteCollection->expects($this->once())
            ->method('getAllIds')
            ->willReturn($quoteIds);

        $this->publisher->expects($this->exactly(2))
            ->method('publish');

        $this->exportOmittedAbandonedCarts->run();
    }
}
