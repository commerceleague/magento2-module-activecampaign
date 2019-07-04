<?php
declare(strict_types=1);
/**
 */

namespace CommerceLeague\ActiveCampaign\Test\Unit\MessageQueue;

use CommerceLeague\ActiveCampaign\Api\ContactRepositoryInterface;
use CommerceLeague\ActiveCampaign\Api\Data\ContactInterface;
use CommerceLeague\ActiveCampaign\Logger\Logger;
use CommerceLeague\ActiveCampaign\MessageQueue\SyncContactConsumer;
use CommerceLeague\ActiveCampaignApi\Api\ContactApiResourceInterface;
use CommerceLeague\ActiveCampaignApi\Exception\HttpException;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use CommerceLeague\ActiveCampaign\Helper\Client as ClientHelper;

class SyncContactConsumerTest extends TestCase
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
     * @var MockObject|ClientHelper
     */
    protected $clientHelper;

    /**
     * @var MockObject|ContactInterface
     */
    protected $contact;

    /**
     * @var MockObject|ContactApiResourceInterface
     */
    protected $contactApi;

    /**
     * @var SyncContactConsumer
     */
    protected $syncContactConsumer;

    protected function setUp()
    {
        $this->contactRepository = $this->createMock(ContactRepositoryInterface::class);
        $this->logger = $this->createMock(Logger::class);
        $this->clientHelper = $this->createMock(ClientHelper::class);
        $this->contact = $this->createMock(ContactInterface::class);
        $this->contactApi = $this->createMock(ContactApiResourceInterface::class);

        $this->syncContactConsumer = new SyncContactConsumer(
            $this->contactRepository,
            $this->logger,
            $this->clientHelper
        );
    }

    public function testConsumeApiResponseException()
    {
        $email = 'example@example.com';
        $request = ['email' => $email];

        $this->contactRepository->expects($this->once())
            ->method('getOrCreateByEmail')
            ->with($email)
            ->willReturn($this->contact);

        $this->clientHelper->expects($this->once())
            ->method('getContactApi')
            ->willReturn($this->contactApi);

        /** @var MockObject|HttpException $httpException */
        $httpException = $this->createMock(HttpException::class);

        $this->contactApi->expects($this->once())
            ->method('upsert')
            ->with(['contact' => $request])
            ->willThrowException($httpException);

        $this->logger->expects($this->once())
            ->method('error');

        $this->contact->expects($this->never())
            ->method('setActiveCampaignId');

        $this->syncContactConsumer->consume(json_encode(['email' => $email, 'request' => $request]));
    }

    public function testConsume()
    {
        $email = 'example@example.com';
        $request = ['email' => $email];
        $activeCampaignId = 123;
        $response = ['contact' => ['id' => $activeCampaignId]];

        $this->contactRepository->expects($this->once())
            ->method('getOrCreateByEmail')
            ->with($email)
            ->willReturn($this->contact);

        $this->clientHelper->expects($this->once())
            ->method('getContactApi')
            ->willReturn($this->contactApi);

        $this->contactApi->expects($this->once())
            ->method('upsert')
            ->with(['contact' => $request])
            ->willReturn($response);

        $this->contact->expects($this->once())
            ->method('setActiveCampaignId')
            ->with($activeCampaignId)
            ->willReturnSelf();

        $this->contactRepository->expects($this->once())
            ->method('save')
            ->with($this->contact);

        $this->syncContactConsumer->consume(json_encode(['email' => $email, 'request' => $request]));
    }
}
