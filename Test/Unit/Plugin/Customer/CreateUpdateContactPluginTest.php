<?php
/**
 */

namespace CommerceLeague\ActiveCampaign\Test\Unit\Plugin\Customer;

use CommerceLeague\ActiveCampaign\Api\ContactRepositoryInterface;
use CommerceLeague\ActiveCampaign\Logger\Logger;
use CommerceLeague\ActiveCampaign\MessageQueue\Contact\CreateUpdatePublisher;
use CommerceLeague\ActiveCampaign\Model\Contact;
use CommerceLeague\ActiveCampaign\Model\ContactFactory;
use CommerceLeague\ActiveCampaign\Plugin\Customer\CreateUpdateContactPlugin;
use Magento\Customer\Api\Data\CustomerInterface;
use Magento\Customer\Model\ResourceModel\CustomerRepository;
use Magento\Framework\DataObject\Copy as ObjectCopyService;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Phrase;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class CreateUpdateContactPluginTest extends TestCase
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
     * @var MockObject|CustomerRepository
     */
    protected $subject;

    /**
     * @var MockObject|CustomerInterface
     */
    protected $customer;

    /**
     * @var CreateUpdateContactPlugin
     */
    protected $createUpdateContactPlugin;

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

        $this->subject = $this->getMockBuilder(CustomerRepository::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->customer = $this->createMock(CustomerInterface::class);

        $this->createUpdateContactPlugin = new CreateUpdateContactPlugin(
            $this->contactRepository,
            $this->contactFactory,
            $this->objectCopyService,
            $this->createUpdatePublisher,
            $this->logger
        );
    }

    public function testAfterSaveGetsContactFromRepository()
    {
        $customerId = 123;

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

        $this->createUpdateContactPlugin->afterSave($this->subject, $this->customer);
    }

    public function testAfterSaveCreatesNewContact()
    {
        $customerId = 123;

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

        $this->createUpdateContactPlugin->afterSave($this->subject, $this->customer);
    }

    public function testAfterSaveLogsException()
    {
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

        $this->createUpdateContactPlugin->afterSave($this->subject, $this->customer);
    }
}