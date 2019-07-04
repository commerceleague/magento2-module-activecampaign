<?php
declare(strict_types=1);
/**
 */

namespace CommerceLeague\ActiveCampaign\Test\Observer\Newsletter;

use CommerceLeague\ActiveCampaign\Helper\Config as ConfigHelper;
use CommerceLeague\ActiveCampaign\Observer\Newsletter\SyncContactObserver;
use CommerceLeague\ActiveCampaign\Service\Contact\SyncContactService;
use Magento\Framework\Event;
use Magento\Framework\Event\Observer;
use Magento\Newsletter\Model\Subscriber;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class SyncContactObserverTest extends TestCase
{
    /**
     * @var MockObject|ConfigHelper
     */
    protected $configHelper;

    /**
     * @var MockObject|SyncContactService
     */
    protected $syncContactService;

    /**
     * @var MockObject|Observer
     */
    protected $observer;

    /**
     * @var MockObject|Event
     */
    protected $event;

    /**
     * @var MockObject|Subscriber
     */
    protected $subscriber;

    /**
     * @var SyncContactObserver
     */
    protected $syncContactObserver;

    protected function setUp()
    {
        $this->configHelper = $this->createMock(ConfigHelper::class);
        $this->syncContactService = $this->createMock(SyncContactService::class);
        $this->observer = $this->createMock(Observer::class);
        $this->event = $this->createMock(Event::class);
        $this->subscriber = $this->createMock(Subscriber::class);

        $this->syncContactObserver = new SyncContactObserver(
            $this->configHelper,
            $this->syncContactService
        );
    }

    public function testExecuteApiDisabled()
    {
        $this->configHelper->expects($this->once())
            ->method('isApiEnabled')
            ->willReturn(false);

        $this->observer->expects($this->never())
            ->method('getEvent');

        $this->syncContactObserver->execute($this->observer);
    }

    public function testExecuteWithSubscriberAsCustomer()
    {
        $this->configHelper->expects($this->once())
            ->method('isApiEnabled')
            ->willReturn(true);

        $this->observer->expects($this->once())
            ->method('getEvent')
            ->willReturn($this->event);

        $this->event->expects($this->once())
            ->method('getData')
            ->with('subscriber')
            ->willReturn($this->subscriber);

        $this->subscriber->expects($this->once())
            ->method('getData')
            ->with('customer_id')
            ->willReturn(123);

        $this->syncContactService->expects($this->never())
            ->method('syncWithSubscriber');

        $this->syncContactObserver->execute($this->observer);
    }

    public function testExecute()
    {
        $this->configHelper->expects($this->once())
            ->method('isApiEnabled')
            ->willReturn(true);

        $this->observer->expects($this->once())
            ->method('getEvent')
            ->willReturn($this->event);

        $this->event->expects($this->once())
            ->method('getData')
            ->with('subscriber')
            ->willReturn($this->subscriber);

        $this->subscriber->expects($this->once())
            ->method('getData')
            ->with('customer_id')
            ->willReturn(null);

        $this->syncContactService->expects($this->once())
            ->method('syncWithSubscriber')
            ->with($this->subscriber);

        $this->syncContactObserver->execute($this->observer);
    }
}
