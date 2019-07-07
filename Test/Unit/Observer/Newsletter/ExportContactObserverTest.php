<?php
declare(strict_types=1);
/**
 */

namespace CommerceLeague\ActiveCampaign\Test\Observer\Newsletter;

use CommerceLeague\ActiveCampaign\Helper\Config as ConfigHelper;
use CommerceLeague\ActiveCampaign\MessageQueue\Topics;
use CommerceLeague\ActiveCampaign\Observer\Newsletter\ExportContactObserver;
use Magento\Framework\Event;
use Magento\Framework\Event\Observer;
use Magento\Framework\MessageQueue\PublisherInterface;
use Magento\Newsletter\Model\Subscriber;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class ExportContactObserverTest extends TestCase
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
     * @var MockObject|Subscriber
     */
    protected $subscriber;

    /**
     * @var ExportContactObserver
     */
    protected $exportContactObserver;

    protected function setUp()
    {
        $this->configHelper = $this->createMock(ConfigHelper::class);
        $this->publisher = $this->createMock(PublisherInterface::class);
        $this->observer = $this->createMock(Observer::class);
        $this->event = $this->createMock(Event::class);
        $this->subscriber = $this->createMock(Subscriber::class);

        $this->exportContactObserver = new ExportContactObserver(
            $this->configHelper,
            $this->publisher
        );
    }

    public function testExecuteApiDisabled()
    {
        $this->configHelper->expects($this->once())
            ->method('isApiEnabled')
            ->willReturn(false);

        $this->observer->expects($this->never())
            ->method('getEvent');

        $this->exportContactObserver->execute($this->observer);
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

        $this->publisher->expects($this->never())
            ->method('publish');

        $this->exportContactObserver->execute($this->observer);
    }

    public function testExecute()
    {
        $email = 'example@example.com';

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

        $this->subscriber->expects($this->once())
            ->method('getEmail')
            ->willReturn($email);

        $this->publisher->expects($this->once())
            ->method('publish')
            ->with(
                Topics::NEWSLETTER_CONTACT_EXPORT,
                json_encode(['email' => $email])
            );

        $this->exportContactObserver->execute($this->observer);
    }
}
