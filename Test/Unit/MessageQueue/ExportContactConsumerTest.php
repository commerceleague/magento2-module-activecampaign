<?php
declare(strict_types=1);
/**
 */

namespace CommerceLeague\ActiveCampaign\Test\Unit\MessageQueue;

use CommerceLeague\ActiveCampaign\Api\ContactRepositoryInterface;
use CommerceLeague\ActiveCampaign\Api\Data\ContactInterface;
use CommerceLeague\ActiveCampaign\Logger\Logger;
use CommerceLeague\ActiveCampaign\MessageQueue\ExportContactConsumer;
use CommerceLeague\ActiveCampaignApi\Api\ContactApiResourceInterface;
use CommerceLeague\ActiveCampaignApi\Exception\HttpException;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use CommerceLeague\ActiveCampaign\Gateway\Client;

class ExportContactConsumerTest extends TestCase
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
     * @var MockObject|Client
     */
    protected $client;

    /**
     * @var MockObject|ContactInterface
     */
    protected $contact;

    /**
     * @var MockObject|ContactApiResourceInterface
     */
    protected $contactApi;

    /**
     * @var ExportContactConsumer
     */
    protected $exportContactConsumer;

    protected function setUp()
    {
        $this->contactRepository = $this->createMock(ContactRepositoryInterface::class);
        $this->logger = $this->createMock(Logger::class);
        $this->client = $this->createMock(Client::class);
        $this->contact = $this->createMock(ContactInterface::class);
        $this->contactApi = $this->createMock(ContactApiResourceInterface::class);

        $this->exportContactConsumer = new ExportContactConsumer(
            $this->contactRepository,
            $this->logger,
            $this->client
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

        $this->client->expects($this->once())
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

        $this->exportContactConsumer->consume(json_encode(['email' => $email, 'request' => $request]));
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

        $this->client->expects($this->once())
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

        $this->exportContactConsumer->consume(json_encode(['email' => $email, 'request' => $request]));
    }
}
