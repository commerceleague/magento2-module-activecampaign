<?php
/**
 */

namespace CommerceLeague\ActiveCampaign\Test\Unit\Service\Contact;

use CommerceLeague\ActiveCampaign\Api\ContactRepositoryInterface;
use CommerceLeague\ActiveCampaign\Api\Data\ContactInterface;
use CommerceLeague\ActiveCampaign\Gateway\Request\ContactRequestBuilder;
use CommerceLeague\ActiveCampaign\Logger\Logger;
use CommerceLeague\ActiveCampaign\MessageQueue\Contact\CreateUpdateMessage;
use CommerceLeague\ActiveCampaign\MessageQueue\Contact\CreateUpdatePublisher;
use CommerceLeague\ActiveCampaign\Service\Contact\CreateUpdateContactService;
use Magento\Customer\Model\Customer;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Phrase;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class CreateUpdateContactServiceTest extends TestCase
{
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
     * @var MockObject|CreateUpdatePublisher
     */
    protected $createUpdatePublisher;

    /**
     * @var MockObject|Customer
     */
    protected $customer;

    /**
     * @var MockObject|ContactInterface
     */
    protected $contact;

    /**
     * @var CreateUpdateContactService
     */
    protected $createUpdateContactService;

    protected function setUp()
    {
        $this->contactRepository = $this->createMock(ContactRepositoryInterface::class);
        $this->logger = $this->createMock(Logger::class);
        $this->contactRequestBuilder = $this->createMock(ContactRequestBuilder::class);
        $this->createUpdatePublisher = $this->createMock(CreateUpdatePublisher::class);
        $this->customer = $this->createMock(Customer::class);
        $this->contact = $this->createMock(ContactInterface::class);

        $this->createUpdateContactService = new CreateUpdateContactService(
            $this->contactRepository,
            $this->logger,
            $this->contactRequestBuilder,
            $this->createUpdatePublisher
        );
    }

    public function testExecuteContactCouldNotSave()
    {
        $exception = new CouldNotSaveException(new Phrase('an exception'));

        $this->contactRepository->expects($this->once())
            ->method('getOrCreateByCustomer')
            ->willThrowException($exception);

        $this->logger->expects($this->once())
            ->method('critical')
            ->with($exception);

        $this->contactRequestBuilder->expects($this->never())
            ->method('build');

        $this->createUpdatePublisher->expects($this->never())
            ->method('publish');

        $this->createUpdateContactService->execute($this->customer);
    }

    public function testExecute()
    {
        $contactId = 123;

        $this->contactRepository->expects($this->once())
            ->method('getOrCreateByCustomer')
            ->willReturn($this->contact);

        $this->contact->expects($this->once())
            ->method('getId')
            ->willReturn($contactId);

        $this->contactRequestBuilder->expects($this->once())
            ->method('build')
            ->with($this->customer)
            ->willReturn(['request']);

        $this->createUpdatePublisher->expects($this->once())
            ->method('publish')
            ->with($this->isInstanceOf(CreateUpdateMessage::class));

        $this->createUpdateContactService->execute($this->customer);
    }
}