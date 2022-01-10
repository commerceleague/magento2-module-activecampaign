<?php
declare(strict_types=1);
/**
 */

namespace CommerceLeague\ActiveCampaign\Test\Unit\Observer\Sales;

use CommerceLeague\ActiveCampaign\Helper\Config as ConfigHelper;
use CommerceLeague\ActiveCampaign\MessageQueue\Topics;
use CommerceLeague\ActiveCampaign\Observer\Sales\ExportOrderObserver;
use CommerceLeague\ActiveCampaign\Test\Unit\AbstractTestCase;
use Magento\Framework\Event;
use Magento\Framework\Event\Observer;
use Magento\Framework\MessageQueue\PublisherInterface;
use Magento\Sales\Model\Order as MagentoOrder;
use PHPUnit\Framework\MockObject\MockObject;

class ExportOrderObserverTest extends AbstractTestCase
{

    /**
     * @var MockObject|ConfigHelper
     */
    protected $configHelper;

    /**
     * @var MockObject|PublisherInterface
     */
    protected $publisher;

    /**
     * @var MockObject|Observer
     */
    protected $observer;

    /**
     * @var MockObject|Event
     */
    protected $event;

    /**
     * @var MockObject|MagentoOrder
     */
    protected $magentoOrder;

    /**
     * @var ExportOrderObserver
     */
    protected $exportOrderObserver;

    protected function setUp()
    {
        $this->configHelper = $this->createMock(ConfigHelper::class);
        $this->publisher = $this->createMock(PublisherInterface::class);
        $this->observer = $this->createMock(Observer::class);
        $this->event = $this->createMock(Event::class);
        $this->magentoOrder = $this->createMock(MagentoOrder::class);

        $this->exportOrderObserver = new ExportOrderObserver(
            $this->configHelper,
            $this->publisher
        );
    }

    public function testExecuteApiDisabled()
    {
        $this->configHelper->expects($this->once())
            ->method('isEnabled')
            ->willReturn(false);

        $this->observer->expects($this->never())
            ->method('getEvent');

        $this->exportOrderObserver->execute($this->observer);
    }

    public function testExecuteOrderExportDisabled()
    {
        $this->configHelper->expects($this->once())
            ->method('isEnabled')
            ->willReturn(true);

        $this->configHelper->expects($this->once())
            ->method('isOrderExportEnabled')
            ->willReturn(false);

        $this->observer->expects($this->never())
            ->method('getEvent');

        $this->exportOrderObserver->execute($this->observer);
    }

    public function testExecuteWithGuestOrder()
    {
        $this->configHelper->expects($this->once())
            ->method('isEnabled')
            ->willReturn(true);

        $this->configHelper->expects($this->once())
            ->method('isOrderExportEnabled')
            ->willReturn(true);

        $this->observer->expects($this->once())
            ->method('getEvent')
            ->willReturn($this->event);

        $this->event->expects($this->once())
            ->method('getData')
            ->with('order')
            ->willReturn($this->magentoOrder);

        $this->magentoOrder->expects($this->once())
            ->method('getCustomerIsGuest')
            ->willReturn(true);

        $this->publisher->expects($this->atLeastOnce())
            ->method('publish');

        $this->exportOrderObserver->execute($this->observer);
    }

    public function testExecute()
    {
        $magentoOrderId = 123;

        $this->configHelper->expects($this->once())
            ->method('isEnabled')
            ->willReturn(true);

        $this->configHelper->expects($this->once())
            ->method('isOrderExportEnabled')
            ->willReturn(true);

        $this->observer->expects($this->once())
            ->method('getEvent')
            ->willReturn($this->event);

        $this->event->expects($this->once())
            ->method('getData')
            ->with('order')
            ->willReturn($this->magentoOrder);

        $this->magentoOrder->expects($this->once())
            ->method('getCustomerIsGuest')
            ->willReturn(false);

        $this->magentoOrder->expects($this->once())
            ->method('getId')
            ->willReturn($magentoOrderId);

        $this->publisher->expects($this->once())
            ->method('publish')
            ->with(
                Topics::SALES_ORDER_EXPORT,
                json_encode(['magento_order_id' => $magentoOrderId])
            );

        $this->exportOrderObserver->execute($this->observer);
    }
}
