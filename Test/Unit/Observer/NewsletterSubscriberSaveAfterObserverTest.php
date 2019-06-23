<?php
declare(strict_types=1);
/**
 */

namespace CommerceLeague\ActiveCampaign\Test\Unit\Observer\Customer;

use CommerceLeague\ActiveCampaign\Helper\Config as ConfigHelper;
use CommerceLeague\ActiveCampaign\Observer\NewsletterSubscriberSaveAfterObserver;
use CommerceLeague\ActiveCampaign\Service\Contact\CreateUpdateContactService;
use Magento\Framework\Event;
use Magento\Framework\Event\Observer;
use Magento\Newsletter\Model\Subscriber;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class NewsletterSubscriberSaveAfterObserverTest extends TestCase
{
    /**
     * @var MockObject|ConfigHelper
     */
    protected $configHelper;

    /**
     * @var MockObject|CreateUpdateContactService
     */
    protected $createUpdateContactService;

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
     * @var NewsletterSubscriberSaveAfterObserver
     */
    protected $newsletterSubscriberSaveAfterObserver;

    protected function setUp()
    {
        $this->configHelper = $this->createMock(ConfigHelper::class);
        $this->createUpdateContactService = $this->createMock(CreateUpdateContactService::class);
        $this->observer = $this->createMock(Observer::class);
        $this->event = $this->createMock(Event::class);
        $this->subscriber = $this->createMock(Subscriber::class);

        $this->newsletterSubscriberSaveAfterObserver = new NewsletterSubscriberSaveAfterObserver(
            $this->configHelper,
            $this->createUpdateContactService
        );
    }

    public function testExecuteApiNotEnabled()
    {
        $this->configHelper->expects($this->once())
            ->method('isApiEnabled')
            ->willReturn(false);

        $this->observer->expects($this->never())
            ->method('getEvent');

        $this->newsletterSubscriberSaveAfterObserver->execute($this->observer);
    }

    public function testExecuteApiEnabled()
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

        $this->createUpdateContactService->expects($this->once())
            ->method('executeWithSubscriber')
            ->with($this->subscriber);

        $this->newsletterSubscriberSaveAfterObserver->execute($this->observer);
    }
}
