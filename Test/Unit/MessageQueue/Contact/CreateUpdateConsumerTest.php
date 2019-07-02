<?php
/**
 */

namespace CommerceLeague\ActiveCampaign\Test\Unit\MessageQueue\Contact;

use CommerceLeague\ActiveCampaign\Api\ContactRepositoryInterface;
use CommerceLeague\ActiveCampaign\Api\Data\ContactInterface;
use CommerceLeague\ActiveCampaign\Logger\Logger;
use CommerceLeague\ActiveCampaign\MessageQueue\Contact\CreateUpdateConsumer;
use CommerceLeague\ActiveCampaign\MessageQueue\Contact\CreateUpdateMessage;
use CommerceLeague\ActiveCampaignApi\Api\ContactApiResourceInterface;
use CommerceLeague\ActiveCampaignApi\Exception\HttpException;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Phrase;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use CommerceLeague\ActiveCampaign\Helper\Client as ClientHelper;

class CreateUpdateConsumerTest extends TestCase
{
    /**
     * @var MockObject|ClientHelper
     */
    protected $clientHelper;

    /**
     * @var MockObject|Logger
     */
    protected $logger;

    /**
     * @var MockObject|ContactRepositoryInterface
     */
    protected $contactRepository;

    /**
     * @var MockObject|CreateUpdateMessage
     */
    protected $createUpdateMessage;

    /**
     * @var MockObject|ContactInterface
     */
    protected $contact;

    /**
     * @var MockObject|ContactApiResourceInterface
     */
    protected $contactApi;

    /**
     * @var CreateUpdateConsumer
     */
    protected $createUpdateConsumer;

    protected function setUp()
    {
        $this->clientHelper = $this->createMock(ClientHelper::class);
        $this->logger = $this->createMock(Logger::class);
        $this->contactRepository = $this->createMock(ContactRepositoryInterface::class);
        $this->createUpdateMessage = $this->createMock(CreateUpdateMessage::class);
        $this->contact = $this->createMock(ContactInterface::class);
        $this->contactApi = $this->createMock(ContactApiResourceInterface::class);

        $this->createUpdateConsumer = new CreateUpdateConsumer(
            $this->clientHelper,
            $this->logger,
            $this->contactRepository
        );
    }

    public function testConsumeWithUnknownCustomer()
    {
        $entityId = 123;

        $this->contactRepository->expects($this->once())
            ->method('getById')
            ->with($entityId)
            ->willReturn($this->contact);

        $this->createUpdateMessage->expects($this->any())
            ->method('getEntityId')
            ->willReturn($entityId);

        $this->contact->expects($this->once())
            ->method('getId')
            ->willReturn(null);

        $this->logger->expects($this->once())
            ->method('error')
            ->with(__('Unable to find contact with id "%1".', $entityId));

        $this->createUpdateMessage->expects($this->never())
            ->method('getSerializedRequest');

        $this->createUpdateConsumer->consume($this->createUpdateMessage);
    }

    public function testConsumeApiCallFails()
    {
        $entityId = 123;
        $request = ['request'];
        /** @var MockObject|HttpException $exception */
        $exception = $this->createMock(HttpException::class);

        $this->contactRepository->expects($this->once())
            ->method('getById')
            ->with($entityId)
            ->willReturn($this->contact);

        $this->createUpdateMessage->expects($this->any())
            ->method('getEntityId')
            ->willReturn($entityId);

        $this->contact->expects($this->once())
            ->method('getId')
            ->willReturn($entityId);

        $this->createUpdateMessage->expects($this->once())
            ->method('getSerializedRequest')
            ->willReturn(json_encode($request));

        $this->clientHelper->expects($this->once())
            ->method('getContactApi')
            ->willReturn($this->contactApi);

        $this->contactApi->expects($this->once())
            ->method('upsert')
            ->with(['contact' => $request])
            ->willThrowException($exception);

        $this->logger->expects($this->once())
            ->method('error');

        $this->contact->expects($this->never())
            ->method('setActiveCampaignId');

        $this->createUpdateConsumer->consume($this->createUpdateMessage);
    }

    public function testConsumeContactCouldNotSave()
    {
        $entityId = 123;
        $activeCampaignId = 456;
        $request = ['request'];

        $this->contactRepository->expects($this->once())
            ->method('getById')
            ->with($entityId)
            ->willReturn($this->contact);

        $this->createUpdateMessage->expects($this->any())
            ->method('getEntityId')
            ->willReturn($entityId);

        $this->contact->expects($this->once())
            ->method('getId')
            ->willReturn($entityId);

        $this->createUpdateMessage->expects($this->once())
            ->method('getSerializedRequest')
            ->willReturn(json_encode($request));

        $this->clientHelper->expects($this->once())
            ->method('getContactApi')
            ->willReturn($this->contactApi);

        $this->contactApi->expects($this->once())
            ->method('upsert')
            ->with(['contact' => $request])
            ->willReturn(['contact' => ['id' => $activeCampaignId]]);

        $this->contact->expects($this->once())
            ->method('setActiveCampaignId')
            ->with($activeCampaignId);

        $this->contactRepository->expects($this->once())
            ->method('save')
            ->with($this->contact)
            ->willThrowException(new CouldNotSaveException(new Phrase('an exception')));

        $this->logger->expects($this->once())
            ->method('error')
            ->with('an exception');

        $this->createUpdateConsumer->consume($this->createUpdateMessage);
    }

    public function testConsume()
    {
        $entityId = 123;
        $activeCampaignId = 456;
        $request = ['request'];

        $this->contactRepository->expects($this->once())
            ->method('getById')
            ->with($entityId)
            ->willReturn($this->contact);

        $this->createUpdateMessage->expects($this->any())
            ->method('getEntityId')
            ->willReturn($entityId);

        $this->contact->expects($this->once())
            ->method('getId')
            ->willReturn($entityId);

        $this->createUpdateMessage->expects($this->once())
            ->method('getSerializedRequest')
            ->willReturn(json_encode($request));

        $this->clientHelper->expects($this->once())
            ->method('getContactApi')
            ->willReturn($this->contactApi);

        $this->contactApi->expects($this->once())
            ->method('upsert')
            ->with(['contact' => $request])
            ->willReturn(['contact' => ['id' => $activeCampaignId]]);

        $this->contact->expects($this->once())
            ->method('setActiveCampaignId')
            ->with($activeCampaignId);

        $this->contactRepository->expects($this->once())
            ->method('save')
            ->with($this->contact);

        $this->logger->expects($this->never())
            ->method('error');

        $this->createUpdateConsumer->consume($this->createUpdateMessage);
    }
}
