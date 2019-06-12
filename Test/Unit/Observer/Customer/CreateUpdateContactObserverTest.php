<?php
/**
 */

namespace CommerceLeague\ActiveCampaign\Test\Unit\Observer\Customer;

use CommerceLeague\ActiveCampaign\Api\ContactRepositoryInterface;
use CommerceLeague\ActiveCampaign\Logger\Logger;
use CommerceLeague\ActiveCampaign\MessageQueue\Contact\CreateUpdatePublisher;
use CommerceLeague\ActiveCampaign\Model\Contact;
use CommerceLeague\ActiveCampaign\Model\ContactFactory;
use CommerceLeague\ActiveCampaign\Observer\Customer\CreateUpdateContactObserver;
use Magento\Customer\Model\Customer;
use Magento\Framework\Event;
use Magento\Framework\Event\Observer;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Phrase;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Magento\Framework\DataObject\Copy as ObjectCopyService;

class CreateUpdateContactObserverTest extends TestCase
{
    /**
     * @var MockObject|ContactRepositoryInterface
     */
    protected $contactRepository;

    /**
     * @var MockObject|ContactFactory
     */
    protected $contactFactory;

    /**
     * @var MockObject|Contact
     */
    protected $contact;

    /**
     * @var MockObject|ObjectCopyService
     */
    protected $objectCopyService;

    /**
     * @var MockObject|CreateUpdatePublisher
     */
    protected $createUpdatePublisher;

    /**
     * @var MockObject|Logger
     */
    protected $logger;

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
     * @var CreateUpdateContactObserver
     */
    protected $createUpdateContactObserver;

    protected function setUp()
    {
        $this->contactRepository = $this->getMockBuilder(ContactRepositoryInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->contactFactory = $this->getMockBuilder(ContactFactory::class)
            ->disableOriginalConstructor()
            ->setMethods(['create'])
            ->getMock();

        $this->contact = $this->getMockBuilder(Contact::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->contactFactory->expects($this->any())
            ->method('create')
            ->willReturn($this->contact);

        $this->objectCopyService = $this->createMock(ObjectCopyService::class);

        $this->createUpdatePublisher = $this->createMock(CreateUpdatePublisher::class);

        $this->logger = $this->createMock(Logger::class);

        $this->observer = $this
            ->getMockBuilder(Observer::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->event = $this
            ->getMockBuilder(Event::class)
            ->setMethods(['getData'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->customer = $this->createMock(Customer::class);

        $this->createUpdateContactObserver = new CreateUpdateContactObserver(
            $this->contactRepository,
            $this->contactFactory,
            $this->objectCopyService,
            $this->createUpdatePublisher,
            $this->logger
        );
    }

    public function testExecuteGetsContactFromRepository()
    {
        $customerId = 123;

        $this->observer->expects($this->once())
            ->method('getEvent')
            ->willReturn($this->event);

        $this->event->expects($this->once())
            ->method('getData')
            ->with('customer')
            ->willReturn($this->customer);

        $this->customer->expects($this->once())
            ->method('getId')
            ->willReturn($customerId);

        $this->contactRepository->expects($this->once())
            ->method('getByCustomerId')
            ->with($customerId)
            ->willReturn($this->contact);

        $this->contactFactory->expects($this->never())
            ->method('create');

        $this->objectCopyService->expects($this->once())
            ->method('copyFieldsetToTarget')
            ->with(
                'activecampaign_convert_customer',
                'to_contact',
                $this->customer,
                $this->contact
            );

        $this->createUpdateContactObserver->execute($this->observer);
    }

    public function testExecuteCreatesNewContact()
    {
        $customerId = 123;

        $this->observer->expects($this->once())
            ->method('getEvent')
            ->willReturn($this->event);

        $this->event->expects($this->once())
            ->method('getData')
            ->with('customer')
            ->willReturn($this->customer);

        $this->customer->expects($this->once())
            ->method('getId')
            ->willReturn($customerId);

        $this->contactRepository->expects($this->once())
            ->method('getByCustomerId')
            ->with($customerId)
            ->willThrowException(new NoSuchEntityException());

        $this->contactFactory->expects($this->once())
            ->method('create')
            ->willReturn($this->contact);

        $this->objectCopyService->expects($this->once())
            ->method('copyFieldsetToTarget')
            ->with(
                'activecampaign_convert_customer',
                'to_contact',
                $this->customer,
                $this->contact
            );

        $this->createUpdateContactObserver->execute($this->observer);
    }

    public function testExecuteLogsException()
    {
        $this->observer->expects($this->once())
            ->method('getEvent')
            ->willReturn($this->event);

        $this->event->expects($this->once())
            ->method('getData')
            ->with('customer')
            ->willReturn($this->customer);

        $this->contactRepository->expects($this->once())
            ->method('getByCustomerId')
            ->willReturn($this->contact);

        $this->objectCopyService->expects($this->once())
            ->method('copyFieldsetToTarget')
            ->with(
                'activecampaign_convert_customer',
                'to_contact',
                $this->customer,
                $this->contact
            );

        $exception = new CouldNotSaveException(new Phrase('an exception message'));

        $this->contactRepository->expects($this->once())
            ->method('save')
            ->with($this->contact)
            ->willThrowException($exception);

        $this->logger->expects($this->once())
            ->method('critical')
            ->with($exception);

        $this->createUpdateContactObserver->execute($this->observer);
    }
}
