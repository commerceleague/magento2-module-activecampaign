<?php
/**
 */

namespace CommerceLeague\ActiveCampaign\Test\Unit\Observer\Customer;

use CommerceLeague\ActiveCampaign\Api\ContactRepositoryInterface;
use CommerceLeague\ActiveCampaign\Gateway\Request\ContactRequestBuilder;
use CommerceLeague\ActiveCampaign\Helper\Config as ConfigHelper;
use CommerceLeague\ActiveCampaign\Logger\Logger;
use CommerceLeague\ActiveCampaign\MessageQueue\Contact\CreateUpdateMessage;
use CommerceLeague\ActiveCampaign\MessageQueue\Contact\Topics;
use CommerceLeague\ActiveCampaign\Model\Contact;
use CommerceLeague\ActiveCampaign\Observer\Customer\CreateUpdateContactObserver;
use Magento\Customer\Model\Customer;
use Magento\Framework\Event;
use Magento\Framework\Event\Observer;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\MessageQueue\PublisherInterface;
use Magento\Framework\Phrase;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Class CreateUpdateContactObserverTest
 */
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
     * @var MockObject|ContactRequestBuilder
     */
    protected $contactRequestBuilder;

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
     * @var MockObject|Customer
     */
    protected $customer;

    /**
     * @var MockObject|Contact
     */
    protected $contact;

    /**
     * @var CreateUpdateContactObserver
     */
    protected $createUpdateContactObserver;

    protected function setUp()
    {
        $this->configHelper = $this->createMock(ConfigHelper::class);
        $this->contactRepository = $this->createMock(ContactRepositoryInterface::class);
        $this->logger = $this->createMock(Logger::class);
        $this->contactRequestBuilder = $this->createMock(ContactRequestBuilder::class);
        $this->publisher = $this->createMock(PublisherInterface::class);
        $this->observer = $this->createMock(Observer::class);
        $this->event = $this->createMock(Event::class);
        $this->customer = $this->createMock(Customer::class);
        $this->contact = $this->createMock(Contact::class);

        $this->createUpdateContactObserver = new CreateUpdateContactObserver(
            $this->configHelper,
            $this->contactRepository,
            $this->logger,
            $this->contactRequestBuilder,
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

        $this->createUpdateContactObserver->execute($this->observer);
    }

    public function testExecuteWithUnknownContact()
    {
        $customerId = 123;
        $contactId = 456;
        $request = [];

        $this->configHelper->expects($this->once())
            ->method('isApiEnabled')
            ->willReturn(true);

        $this->observer->expects($this->once())
            ->method('getEvent')
            ->willReturn($this->event);

        $this->event->expects($this->once())
            ->method('getData')
            ->with('customer')
            ->willReturn($this->customer);

        $this->customer->expects($this->any())
            ->method('getId')
            ->willReturn($customerId);

        $this->contactRepository->expects($this->once())
            ->method('getByCustomerId')
            ->with($customerId)
            ->willReturn($this->contact);

        $this->contact->expects($this->at(0))
            ->method('getId')
            ->willReturn(null);

        $this->contact->expects($this->once())
            ->method('setCustomerId')
            ->with($customerId)
            ->willReturnSelf();

        $this->contactRepository->expects($this->once())
            ->method('save')
            ->with($this->contact)
            ->willReturn($this->contact);

        $this->contactRequestBuilder->expects($this->once())
            ->method('build')
            ->with($this->customer)
            ->willReturn($request);

        $this->contact->expects($this->at(1))
            ->method('getId')
            ->willReturn($contactId);

        $this->publisher->expects($this->once())
            ->method('publish')
            ->with(
                Topics::CREATE_UPDATE,
                $this->isInstanceOf(CreateUpdateMessage::class)
            );

        $this->createUpdateContactObserver->execute($this->observer);
    }

    public function testExecuteWithExistingContact()
    {
        $customerId = 123;
        $contactId = 456;
        $request = [];

        $this->configHelper->expects($this->once())
            ->method('isApiEnabled')
            ->willReturn(true);

        $this->observer->expects($this->once())
            ->method('getEvent')
            ->willReturn($this->event);

        $this->event->expects($this->once())
            ->method('getData')
            ->with('customer')
            ->willReturn($this->customer);

        $this->customer->expects($this->any())
            ->method('getId')
            ->willReturn($customerId);

        $this->contactRepository->expects($this->once())
            ->method('getByCustomerId')
            ->with($customerId)
            ->willReturn($this->contact);

        $this->contact->expects($this->any())
            ->method('getId')
            ->willReturn($contactId);

        $this->contact->expects($this->never())
            ->method('setCustomerId');

        $this->contactRepository->expects($this->never())
            ->method('save');

        $this->contactRequestBuilder->expects($this->once())
            ->method('build')
            ->with($this->customer)
            ->willReturn($request);

        $this->contact->expects($this->at(1))
            ->method('getId')
            ->willReturn($contactId);

        $this->publisher->expects($this->once())
            ->method('publish')
            ->with(
                Topics::CREATE_UPDATE,
                $this->isInstanceOf(CreateUpdateMessage::class)
            );

        $this->createUpdateContactObserver->execute($this->observer);
    }

    public function testExecuteContactCouldNotSave()
    {
        $customerId = 123;
        $exception = new CouldNotSaveException(new Phrase('an exception'));

        $this->configHelper->expects($this->once())
            ->method('isApiEnabled')
            ->willReturn(true);

        $this->observer->expects($this->once())
            ->method('getEvent')
            ->willReturn($this->event);

        $this->event->expects($this->once())
            ->method('getData')
            ->with('customer')
            ->willReturn($this->customer);

        $this->customer->expects($this->any())
            ->method('getId')
            ->willReturn($customerId);

        $this->contactRepository->expects($this->once())
            ->method('getByCustomerId')
            ->with($customerId)
            ->willReturn($this->contact);

        $this->contact->expects($this->at(0))
            ->method('getId')
            ->willReturn(null);

        $this->contact->expects($this->once())
            ->method('setCustomerId')
            ->with($customerId)
            ->willReturnSelf();

        $this->contactRepository->expects($this->once())
            ->method('save')
            ->willThrowException($exception);

        $this->contactRequestBuilder->expects($this->never())
            ->method('build');

        $this->publisher->expects($this->never())
            ->method('publish');

        $this->logger->expects($this->once())
            ->method('critical')
            ->with($exception);

        $this->createUpdateContactObserver->execute($this->observer);
    }
}