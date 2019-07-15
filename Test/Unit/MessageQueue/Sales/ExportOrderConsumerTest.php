<?php
declare(strict_types=1);
/**
 */

namespace CommerceLeague\ActiveCampaign\Test\Unit\MessageQueue\Sales;

use CommerceLeague\ActiveCampaign\Api\Data\OrderInterface;
use CommerceLeague\ActiveCampaign\Api\OrderRepositoryInterface;
use CommerceLeague\ActiveCampaign\Gateway\Client;
use CommerceLeague\ActiveCampaign\Gateway\Request\OrderBuilder as OrderRequestBuilder;
use CommerceLeague\ActiveCampaign\Logger\Logger;
use CommerceLeague\ActiveCampaign\MessageQueue\Sales\ExportOrderConsumer;
use CommerceLeague\ActiveCampaignApi\Api\OrderApiResourceInterface;
use CommerceLeague\ActiveCampaignApi\Exception\HttpException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Phrase;
use Magento\Sales\Api\OrderRepositoryInterface as MagentoOrderRepositoryInterface;
use Magento\Sales\Model\Order as MagentoOrder;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class ExportOrderConsumerTest extends TestCase
{
    /**
     * @var MockObject|MagentoOrderRepositoryInterface
     */
    protected $magentoOrderRepository;

    /**
     * @var MockObject|Logger
     */
    protected $logger;

    /**
     * @var MockObject|OrderRepositoryInterface
     */
    protected $orderRepository;

    /**
     * @var MockObject|OrderRequestBuilder
     */
    protected $orderRequestBuilder;

    /**
     * @var MockObject|Client
     */
    protected $client;

    /**
     * @var MockObject|OrderApiResourceInterface
     */
    protected $orderApi;

    /**
     * @var MockObject|MagentoOrder
     */
    protected $magentoOrder;

    /**
     * @var MockObject|OrderInterface
     */
    protected $order;

    /**
     * @var ExportOrderConsumer
     */
    protected $exportOrderConsumer;

    protected function setUp()
    {
        $this->magentoOrderRepository = $this->createMock(MagentoOrderRepositoryInterface::class);
        $this->logger = $this->createMock(Logger::class);
        $this->orderRepository = $this->createMock(OrderRepositoryInterface::class);
        $this->orderRequestBuilder = $this->createMock(OrderRequestBuilder::class);
        $this->client = $this->createMock(Client::class);
        $this->orderApi = $this->createMock(OrderApiResourceInterface::class);
        $this->magentoOrder = $this->createMock(MagentoOrder::class);
        $this->order = $this->createMock(OrderInterface::class);

        $this->exportOrderConsumer = new ExportOrderConsumer(
            $this->magentoOrderRepository,
            $this->logger,
            $this->orderRepository,
            $this->orderRequestBuilder,
            $this->client
        );
    }

    public function testConsumeWithAbsentMagentoOrder()
    {
        $magentoOrderId = 123;

        $exceptionMessage = 'an exception message';
        $exception = new NoSuchEntityException(new Phrase($exceptionMessage));

        $this->magentoOrderRepository->expects($this->once())
            ->method('get')
            ->with($magentoOrderId)
            ->willThrowException($exception);

        $this->logger->expects($this->once())
            ->method('error')
            ->with($exceptionMessage);

        $this->orderRepository->expects($this->never())
            ->method('getOrCreateByMagentoQuoteId');

       $this->exportOrderConsumer->consume(json_encode(['magento_order_id' => $magentoOrderId]));
    }

    public function testConsumeApiRequestException()
    {
        $magentoOrderId = 123;
        $magentoQuoteId = 456;
        $request = ['request'];

        $this->magentoOrderRepository->expects($this->once())
            ->method('get')
            ->with($magentoOrderId)
            ->willReturn($this->magentoOrder);

        $this->magentoOrder->expects($this->once())
            ->method('getQuoteId')
            ->willReturn($magentoQuoteId);

        $this->orderRepository->expects($this->once())
            ->method('getOrCreateByMagentoQuoteId')
            ->with($magentoQuoteId)
            ->willReturn($this->order);

        $this->orderRequestBuilder->expects($this->once())
            ->method('build')
            ->with($this->magentoOrder)
            ->willReturn($request);

        $this->order->expects($this->once())
            ->method('getActiveCampaignId')
            ->willReturn(null);

        $this->client->expects($this->once())
            ->method('getOrderApi')
            ->willReturn($this->orderApi);

        /** @var MockObject|HttpException $httpException */
        $httpException = $this->createMock(HttpException::class);

        $this->orderApi->expects($this->once())
            ->method('create')
            ->with(['ecomOrder' => $request])
            ->willThrowException($httpException);

        $this->logger->expects($this->once())
            ->method('error');

        $this->order->expects($this->never())
            ->method('setActiveCampaignId');

        $this->exportOrderConsumer->consume(json_encode(['magento_order_id' => $magentoOrderId]));
    }

    public function testConsumeUpdate()
    {
        $magentoOrderId = 123;
        $magentoQuoteId = 456;
        $request = ['request'];
        $activeCampaignId = 789;
        $response = ['ecomOrder' => ['id' => $activeCampaignId]];

        $this->magentoOrderRepository->expects($this->once())
            ->method('get')
            ->with($magentoOrderId)
            ->willReturn($this->magentoOrder);

        $this->magentoOrder->expects($this->once())
            ->method('getQuoteId')
            ->willReturn($magentoQuoteId);

        $this->orderRepository->expects($this->once())
            ->method('getOrCreateByMagentoQuoteId')
            ->with($magentoQuoteId)
            ->willReturn($this->order);

        $this->orderRequestBuilder->expects($this->once())
            ->method('build')
            ->with($this->magentoOrder)
            ->willReturn($request);

        $this->order->expects($this->once())
            ->method('getActiveCampaignId')
            ->willReturn($activeCampaignId);

        $this->client->expects($this->once())
            ->method('getOrderApi')
            ->willReturn($this->orderApi);

        $this->orderApi->expects($this->once())
            ->method('update')
            ->with($activeCampaignId, ['ecomOrder' => $request])
            ->willReturn($response);

        $this->order->expects($this->once())
            ->method('setActiveCampaignId')
            ->with($activeCampaignId)
            ->willReturnSelf();

        $this->orderRepository->expects($this->once())
            ->method('save')
            ->with($this->order);

        $this->exportOrderConsumer->consume(json_encode(['magento_order_id' => $magentoOrderId]));
    }

    public function testConsumeCreate()
    {
        $magentoOrderId = 123;
        $magentoQuoteId = 456;
        $request = ['request'];
        $activeCampaignId = 789;
        $response = ['ecomOrder' => ['id' => $activeCampaignId]];

        $this->magentoOrderRepository->expects($this->once())
            ->method('get')
            ->with($magentoOrderId)
            ->willReturn($this->magentoOrder);

        $this->magentoOrder->expects($this->once())
            ->method('getQuoteId')
            ->willReturn($magentoQuoteId);

        $this->orderRepository->expects($this->once())
            ->method('getOrCreateByMagentoQuoteId')
            ->with($magentoQuoteId)
            ->willReturn($this->order);

        $this->orderRequestBuilder->expects($this->once())
            ->method('build')
            ->with($this->magentoOrder)
            ->willReturn($request);

        $this->order->expects($this->once())
            ->method('getActiveCampaignId')
            ->willReturn(null);

        $this->client->expects($this->once())
            ->method('getOrderApi')
            ->willReturn($this->orderApi);

        $this->orderApi->expects($this->once())
            ->method('create')
            ->with(['ecomOrder' => $request])
            ->willReturn($response);

        $this->order->expects($this->once())
            ->method('setActiveCampaignId')
            ->with($activeCampaignId)
            ->willReturnSelf();

        $this->orderRepository->expects($this->once())
            ->method('save')
            ->with($this->order);

        $this->exportOrderConsumer->consume(json_encode(['magento_order_id' => $magentoOrderId]));
    }

}
