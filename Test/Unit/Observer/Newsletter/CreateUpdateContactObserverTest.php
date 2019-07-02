<?php
declare(strict_types=1);
/**
 */

namespace CommerceLeague\ActiveCampaign\Test\Unit\Observer\Newsletter;

use CommerceLeague\ActiveCampaign\Api\ContactRepositoryInterface;
use CommerceLeague\ActiveCampaign\Api\Data\ContactInterface;
use CommerceLeague\ActiveCampaign\Helper\Config as ConfigHelper;
use CommerceLeague\ActiveCampaign\Logger\Logger;
use CommerceLeague\ActiveCampaign\MessageQueue\Contact\CreateUpdateMessage;
use CommerceLeague\ActiveCampaign\MessageQueue\Contact\CreateUpdateMessageBuilder;
use CommerceLeague\ActiveCampaign\MessageQueue\Topics;
use Magento\Framework\MessageQueue\PublisherInterface;
use CommerceLeague\ActiveCampaign\Observer\Newsletter\CreateUpdateContactObserver;
use Magento\Framework\Event;
use Magento\Framework\Event\Observer;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Phrase;
use Magento\Newsletter\Model\Subscriber;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class CreateUpdateContactObserverTest extends TestCase
{
    /**
     * @var MockObject|ConfigHelper
     */
    protected $configHelper;

    /**
     * @var MockObject|ContactRepositoryInterface
     */
    protected $contactRepository;

    /**
     * @var MockObject|Logger
     */
    protected $logger;

    /**
     * @var MockObject|CreateUpdateMessageBuilder
     */
    protected $createUpdateMessageBuilder;

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
     * @var MockObject|ContactInterface
     */
    protected $contact;

    /**
     * @var MockObject|CreateUpdateMessage
     */
    protected $createUpdateMessage;

    /**
     * @var CreateUpdateContactObserver
     */
    protected $newsletterSubscriberSaveAfterObserver;

    protected function setUp()
    {
        $this->configHelper = $this->createMock(ConfigHelper::class);
        $this->contactRepository = $this->createMock(ContactRepositoryInterface::class);
        $this->logger = $this->createMock(Logger::class);
        $this->createUpdateMessageBuilder = $this->createMock(CreateUpdateMessageBuilder::class);
        $this->publisher = $this->createMock(PublisherInterface::class);
        $this->observer = $this->createMock(Observer::class);
        $this->event = $this->createMock(Event::class);
        $this->subscriber = $this->createMock(Subscriber::class);
        $this->contact = $this->createMock(ContactInterface::class);
        $this->createUpdateMessage = $this->createMock(CreateUpdateMessage::class);

        $this->newsletterSubscriberSaveAfterObserver = new CreateUpdateContactObserver(
            $this->configHelper,
            $this->contactRepository,
            $this->logger,
            $this->createUpdateMessageBuilder,
            $this->publisher
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

    public function testExecuteWithCustomerAsSubscriber()
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

        $this->contactRepository->expects($this->never())
            ->method('getOrCreateBySubscriber');

        $this->newsletterSubscriberSaveAfterObserver->execute($this->observer);
    }

    public function testExecuteWithException()
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

        $exception = new CouldNotSaveException(new Phrase(''));

        $this->contactRepository->expects($this->once())
            ->method('getOrCreateBySubscriber')
            ->with($this->subscriber)
            ->willThrowException($exception);

        $this->logger->expects($this->once())
            ->method('critical')
            ->with($exception);

        $this->publisher->expects($this->never())
            ->method('publish');

        $this->newsletterSubscriberSaveAfterObserver->execute($this->observer);
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

        $this->contactRepository->expects($this->once())
            ->method('getOrCreateBySubscriber')
            ->with($this->subscriber)
            ->willReturn($this->contact);

        $this->createUpdateMessageBuilder->expects($this->once())
            ->method('buildWithSubscriber')
            ->with($this->contact, $this->subscriber)
            ->willReturn($this->createUpdateMessage);

        $this->publisher->expects($this->once())
            ->method('publish')
            ->with(Topics::CONTACT_CREATE_UPDATE, $this->createUpdateMessage);

        $this->newsletterSubscriberSaveAfterObserver->execute($this->observer);
    }
}
