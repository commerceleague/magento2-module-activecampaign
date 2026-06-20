<?php
declare(strict_types=1);
/**
 */

namespace CommerceLeague\ActiveCampaign\Test\Unit\Cron;

use CommerceLeague\ActiveCampaign\Cron\PublishOmittedOrders;
use CommerceLeague\ActiveCampaign\Helper\Config as ConfigHelper;
use CommerceLeague\ActiveCampaign\MessageQueue\Topics;
use CommerceLeague\ActiveCampaign\Model\ResourceModel\Order\CollectionFactory as OrderCollectionFactory;
use CommerceLeague\ActiveCampaign\Test\Unit\AbstractTestCase;
use CommerceLeague\ActiveCampaign\Model\ResourceModel\Order\Collection as OrderCollection;
use Magento\Framework\MessageQueue\PublisherInterface;
use PHPUnit\Framework\MockObject\MockObject;

class ExportOmittedOrdersTest extends AbstractTestCase
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
     * @var MockObject|PublisherInterface
     */
    protected $publisher;

    /**
     * @var PublishOmittedOrders
     */
    protected $exportOmittedOrders;

    protected function setUp(): void
    {
        $this->configHelper = $this->createMock(ConfigHelper::class);

        $this->orderCollectionFactory = $this->getMockBuilder(OrderCollectionFactory::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['create'])
            ->getMock();

        $this->orderCollection = $this->createMock(OrderCollection::class);

        $this->orderCollectionFactory->expects($this->any())
            ->method('create')
            ->willReturn($this->orderCollection);

        $this->publisher = $this->createMock(PublisherInterface::class);

        $this->exportOmittedOrders = new PublishOmittedOrders(
            $this->configHelper,
            $this->orderCollectionFactory,
            $this->publisher
        );
    }

    public function testExecuteDisabled()
    {
        $this->configHelper->expects($this->once())
            ->method('isEnabled')
            ->willReturn(false);

        $this->exportOmittedOrders->run();
    }

    public function testExecuteOrderExportDisabled()
    {
        $this->configHelper->expects($this->once())
            ->method('isEnabled')
            ->willReturn(true);

        $this->configHelper->expects($this->once())
            ->method('isOrderExportEnabled')
            ->willReturn(false);

        $this->exportOmittedOrders->run();
    }

    public function testRun()
    {
        $orderIds = [123, 456];

        $this->configHelper->expects($this->once())
            ->method('isEnabled')
            ->willReturn(true);

        $this->configHelper->expects($this->once())
            ->method('isOrderExportEnabled')
            ->willReturn(true);

        $this->orderCollection->expects($this->once())
            ->method('addOmittedFilter')
            ->willReturnSelf();

        $this->orderCollection->expects($this->once())
            ->method('getAllIds')
            ->willReturn($orderIds);

        $expectedPublishArguments = [
            [Topics::SALES_ORDER_EXPORT, json_encode(['magento_order_id' => $orderIds[0]])],
            [Topics::SALES_ORDER_EXPORT, json_encode(['magento_order_id' => $orderIds[1]])],
        ];
        $actualPublishArguments = [];
        $this->publisher->expects($this->exactly(2))
            ->method('publish')
            ->willReturnCallback(function (...$args) use (&$actualPublishArguments) {
                $actualPublishArguments[] = $args;
            });

        $this->exportOmittedOrders->run();

        $this->assertSame($expectedPublishArguments, $actualPublishArguments);
    }
}
