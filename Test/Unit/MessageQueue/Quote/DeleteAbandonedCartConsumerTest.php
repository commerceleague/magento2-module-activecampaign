<?php
declare(strict_types=1);
/**
 */

namespace CommerceLeague\ActiveCampaign\Test\Unit\MessageQueue\Quote;

use CommerceLeague\ActiveCampaign\Api\OrderRepositoryInterface;
use CommerceLeague\ActiveCampaign\Gateway\Client;
use CommerceLeague\ActiveCampaign\Logger\Logger;
use CommerceLeague\ActiveCampaign\MessageQueue\Quote\DeleteAbandonedCartConsumer;
use CommerceLeague\ActiveCampaign\Test\Unit\AbstractTestCase;
use CommerceLeague\ActiveCampaignApi\Api\OrderApiResourceInterface;
use CommerceLeague\ActiveCampaignApi\Exception\HttpException;
use CommerceLeague\ActiveCampaignApi\Exception\NotFoundHttpException;
use Magento\Framework\Exception\NoSuchEntityException;
use PHPUnit\Framework\MockObject\MockObject;

class DeleteAbandonedCartConsumerTest extends AbstractTestCase
{

    /**
     * @var MockObject|Client
     */
    protected $client;

    /**
     * @var MockObject|OrderApiResourceInterface
     */
    protected $orderApi;

    /**
     * @var MockObject|OrderRepositoryInterface
     */
    protected $orderRepository;

    /**
     * @var MockObject|Logger
     */
    protected $logger;

    /**
     * @var DeleteAbandonedCartConsumer
     */
    protected $deleteAbandonedCartConsumer;

    protected function setUp(): void
    {
        $this->client = $this->createMock(Client::class);
        $this->orderApi = $this->createMock(OrderApiResourceInterface::class);
        $this->orderRepository = $this->createMock(OrderRepositoryInterface::class);
        $this->logger = $this->createMock(Logger::class);

        $this->deleteAbandonedCartConsumer = new DeleteAbandonedCartConsumer(
            $this->logger,
            $this->client,
            $this->orderRepository
        );
    }

    public function testConsumeDeletesAcAndLocalRow()
    {
        $activeCampaignId = 789;
        $entityId = 42;

        $this->client->expects($this->once())
            ->method('getOrderApi')
            ->willReturn($this->orderApi);

        $this->orderApi->expects($this->once())
            ->method('delete')
            ->with($activeCampaignId)
            ->willReturn(true);

        $this->orderRepository->expects($this->once())
            ->method('deleteById')
            ->with($entityId)
            ->willReturn(true);

        $this->deleteAbandonedCartConsumer->consume(json_encode([
            'activecampaign_id' => $activeCampaignId,
            'entity_id' => $entityId,
        ]));
    }

    public function testConsumeNotFoundTreatedAsSuccessDeletesLocalRow()
    {
        $activeCampaignId = 789;
        $entityId = 42;

        $this->client->expects($this->once())
            ->method('getOrderApi')
            ->willReturn($this->orderApi);

        /** @var MockObject|NotFoundHttpException $notFoundHttpException */
        $notFoundHttpException = $this->createMock(NotFoundHttpException::class);

        $this->orderApi->expects($this->once())
            ->method('delete')
            ->with($activeCampaignId)
            ->willThrowException($notFoundHttpException);

        // 404 is logged as info, not as a failure.
        $this->logger->expects($this->never())
            ->method('error');

        $this->orderRepository->expects($this->once())
            ->method('deleteById')
            ->with($entityId)
            ->willReturn(true);

        $this->deleteAbandonedCartConsumer->consume(json_encode([
            'activecampaign_id' => $activeCampaignId,
            'entity_id' => $entityId,
        ]));
    }

    public function testConsumeHttpExceptionLogsFailureAndKeepsLocalRow()
    {
        $activeCampaignId = 789;
        $entityId = 42;

        $this->client->expects($this->once())
            ->method('getOrderApi')
            ->willReturn($this->orderApi);

        /** @var MockObject|HttpException $httpException */
        $httpException = $this->createMock(HttpException::class);

        $this->orderApi->expects($this->once())
            ->method('delete')
            ->with($activeCampaignId)
            ->willThrowException($httpException);

        $this->logger->expects($this->once())
            ->method('error')
            ->with($this->logicalAnd(
                $this->stringContains('entity=abandoned_cart'),
                $this->stringContains('code=delete_failed')
            ));

        // Local row is NOT deleted, so the message can retry.
        $this->orderRepository->expects($this->never())
            ->method('deleteById');

        $this->deleteAbandonedCartConsumer->consume(json_encode([
            'activecampaign_id' => $activeCampaignId,
            'entity_id' => $entityId,
        ]));
    }

    public function testConsumeLocalRowAlreadyGoneIsFine()
    {
        $activeCampaignId = 789;
        $entityId = 42;

        $this->client->expects($this->once())
            ->method('getOrderApi')
            ->willReturn($this->orderApi);

        $this->orderApi->expects($this->once())
            ->method('delete')
            ->with($activeCampaignId)
            ->willReturn(true);

        $this->orderRepository->expects($this->once())
            ->method('deleteById')
            ->with($entityId)
            ->willThrowException(new NoSuchEntityException());

        $this->deleteAbandonedCartConsumer->consume(json_encode([
            'activecampaign_id' => $activeCampaignId,
            'entity_id' => $entityId,
        ]));
    }
}
