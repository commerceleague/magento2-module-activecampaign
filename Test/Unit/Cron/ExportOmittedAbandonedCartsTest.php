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
use CommerceLeague\ActiveCampaign\vendor\Model\ResourceModel\Quote\Collection as QuoteCollection;
use Magento\Framework\MessageQueue\PublisherInterface;
use PHPUnit\Framework\MockObject\MockObject;

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
     * @var PublishOmittedAbandonedCarts
     */
    protected $exportOmittedAbandonedCarts;

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

        $this->publisher = $this->createMock(PublisherInterface::class);

        $this->exportOmittedAbandonedCarts = new PublishOmittedAbandonedCarts(
            $this->configHelper,
            $this->quoteCollectionFactory,
            $this->publisher
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

        $this->quoteCollection->expects($this->once())
            ->method('addAbandonedFilter')
            ->willReturnSelf();

        $this->quoteCollection->expects($this->once())
            ->method('addOmittedFilter')
            ->willReturnSelf();

        $this->quoteCollection->expects($this->once())
            ->method('getAllIds')
            ->willReturn($quoteIds);

        $this->publisher->expects($this->exactly(2))
            ->method('publish');

        $this->publisher->expects($this->at(0))
            ->method('publish')
            ->with(
                Topics::QUOTE_ABANDONED_CART_EXPORT,
                json_encode(['quote_id' => $quoteIds[0]])
            );

        $this->publisher->expects($this->at(1))
            ->method('publish')
            ->with(
                Topics::QUOTE_ABANDONED_CART_EXPORT,
                json_encode(['quote_id' => $quoteIds[1]])
            );

        $this->exportOmittedAbandonedCarts->run();
    }
}
