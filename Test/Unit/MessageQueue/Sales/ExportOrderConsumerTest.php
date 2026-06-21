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
use CommerceLeague\ActiveCampaign\MessageQueue\Topics;
use CommerceLeague\ActiveCampaign\Model\Export\BackoffState;
use CommerceLeague\ActiveCampaign\Model\Export\FailureRecorder;
use CommerceLeague\ActiveCampaign\Test\Unit\AbstractTestCase;
use CommerceLeague\ActiveCampaignApi\Api\OrderApiResourceInterface;
use CommerceLeague\ActiveCampaignApi\Exception\HttpException;
use CommerceLeague\ActiveCampaignApi\Exception\UnprocessableEntityHttpException;
use CommerceLeague\ActiveCampaignApi\Paginator\PageInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\MessageQueue\PublisherInterface;
use Magento\Framework\Phrase;
use Magento\Sales\Api\OrderRepositoryInterface as MagentoOrderRepositoryInterface;
use Magento\Sales\Model\Order as MagentoOrder;
use PHPUnit\Framework\MockObject\MockObject;

class ExportOrderConsumerTest extends AbstractTestCase
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
     * @var MockObject|PublisherInterface
     */
    protected $publisher;

    /**
     * @var MockObject|FailureRecorder
     */
    protected $failureRecorder;

    /**
     * @var MockObject|BackoffState
     */
    protected $backoffState;

    /**
     * @var ExportOrderConsumer
     */
    protected $exportOrderConsumer;

    protected function setUp(): void
    {
        $this->magentoOrderRepository = $this->createMock(MagentoOrderRepositoryInterface::class);
        $this->logger = $this->createMock(Logger::class);
        $this->orderRepository = $this->createMock(OrderRepositoryInterface::class);
        $this->orderRequestBuilder = $this->createMock(OrderRequestBuilder::class);
        $this->client = $this->createMock(Client::class);
        $this->orderApi = $this->createMock(OrderApiResourceInterface::class);
        $this->magentoOrder = $this->createMock(MagentoOrder::class);
        $this->order = $this->createMock(OrderInterface::class);
        $this->publisher = $this->createMock(PublisherInterface::class);
        $this->failureRecorder = $this->createMock(FailureRecorder::class);
        $this->backoffState = $this->createMock(BackoffState::class);

        $this->exportOrderConsumer = new ExportOrderConsumer(
            $this->magentoOrderRepository,
            $this->logger,
            $this->orderRepository,
            $this->orderRequestBuilder,
            $this->client,
            $this->publisher,
            $this->failureRecorder,
            $this->backoffState
        );
    }

    /**
     * Builds a real HttpException carrying the given HTTP status code, so
     * $e->getCode() returns it (getCode() is final and cannot be mocked).
     */
    private function httpExceptionWithCode(int $statusCode): HttpException
    {
        $request = $this->createMock(\Psr\Http\Message\RequestInterface::class);
        $response = $this->createMock(\Psr\Http\Message\ResponseInterface::class);
        $response->method('getStatusCode')->willReturn($statusCode);

        return new HttpException('boom', $request, $response);
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
            ->with($exception);

        $this->orderRepository->expects($this->never())
            ->method('getOrCreateByMagentoQuoteId');

       $this->exportOrderConsumer->consume(json_encode(['magento_order_id' => $magentoOrderId]));
    }

    public function testConsumeApiHttpException()
    {
        $magentoOrderId = 123;
        $magentoQuoteId = 456;
        $request = ['request', 'customerid' => 999];

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

        // Task 6.1: structured failure line with entity type, magento id and AC code.
        $this->logger->expects($this->once())
            ->method('error')
            ->with($this->logicalAnd(
                $this->stringContains('entity=order'),
                $this->stringContains('magento_id=' . $magentoOrderId),
                $this->stringContains('code=http_error')
            ));

        $this->order->expects($this->never())
            ->method('setActiveCampaignId');

        $this->exportOrderConsumer->consume(json_encode(['magento_order_id' => $magentoOrderId]));
    }

    public function testConsumeApiUnprocessableEntityHttpExceptionException()
    {
        $magentoOrderId = 123;
        $magentoQuoteId = 456;
        $resolvedId = 555;
        $request = ['externalid' => 'EXT-1', 'customerid' => 999];
        $responseErrors = [['code' => 'duplicate']];

        $this->magentoOrderRepository->expects($this->once())
            ->method('get')
            ->with($magentoOrderId)
            ->willReturn($this->magentoOrder);

        $this->magentoOrder->expects($this->atLeastOnce())
            ->method('getQuoteId')
            ->willReturn($magentoQuoteId);

        $this->magentoOrder->expects($this->once())
            ->method('getEntityId')
            ->willReturn($magentoOrderId);

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

        $this->client->expects($this->atLeastOnce())
            ->method('getOrderApi')
            ->willReturn($this->orderApi);

        /** @var MockObject|UnprocessableEntityHttpException $unprocessableEntityHttpException */
        $unprocessableEntityHttpException = $this->createMock(UnprocessableEntityHttpException::class);

        $this->orderApi->expects($this->once())
            ->method('create')
            ->with(['ecomOrder' => $request])
            ->willThrowException($unprocessableEntityHttpException);

        $unprocessableEntityHttpException->expects($this->atLeastOnce())
            ->method('getResponseErrors')
            ->willReturn($responseErrors);

        /** @var MockObject|PageInterface $page */
        $page = $this->createMock(PageInterface::class);
        $page->expects($this->atLeastOnce())
            ->method('getItems')
            ->willReturn([['id' => $resolvedId]]);

        $this->orderApi->expects($this->once())
            ->method('listPerPage')
            ->with(1, 0, ['filters' => ['externalid' => 'EXT-1']])
            ->willReturn($page);

        $this->order->expects($this->once())
            ->method('setActiveCampaignId')
            ->with($resolvedId)
            ->willReturnSelf();

        $this->order->expects($this->once())
            ->method('setMagentoOrderId')
            ->with($magentoOrderId)
            ->willReturnSelf();

        $this->failureRecorder->expects($this->once())
            ->method('recordSuccess')
            ->with($this->order);

        $this->orderRepository->expects($this->once())
            ->method('save')
            ->with($this->order);

        $this->exportOrderConsumer->consume(json_encode(['magento_order_id' => $magentoOrderId]));
    }

    public function testDuplicateLookupEmptyDoesNotFatal()
    {
        $magentoOrderId = 123;
        $magentoQuoteId = 456;
        $request = ['externalid' => 'EXT-1', 'customerid' => 999];
        $responseErrors = [['code' => 'duplicate']];

        $this->magentoOrderRepository->expects($this->once())
            ->method('get')
            ->with($magentoOrderId)
            ->willReturn($this->magentoOrder);

        $this->magentoOrder->expects($this->atLeastOnce())
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

        $this->client->expects($this->atLeastOnce())
            ->method('getOrderApi')
            ->willReturn($this->orderApi);

        /** @var MockObject|UnprocessableEntityHttpException $unprocessableEntityHttpException */
        $unprocessableEntityHttpException = $this->createMock(UnprocessableEntityHttpException::class);

        $this->orderApi->expects($this->once())
            ->method('create')
            ->with(['ecomOrder' => $request])
            ->willThrowException($unprocessableEntityHttpException);

        $unprocessableEntityHttpException->expects($this->atLeastOnce())
            ->method('getResponseErrors')
            ->willReturn($responseErrors);

        /** @var MockObject|PageInterface $page */
        $page = $this->createMock(PageInterface::class);
        $page->expects($this->atLeastOnce())
            ->method('getItems')
            ->willReturn([]);

        $this->orderApi->expects($this->once())
            ->method('listPerPage')
            ->with(1, 0, ['filters' => ['externalid' => 'EXT-1']])
            ->willReturn($page);

        $this->order->expects($this->never())
            ->method('setActiveCampaignId');

        // Empty duplicate lookup throws DuplicateNotFoundException, caught by the
        // inner catch which logs and returns BEFORE an outcome is computed; this is
        // not treated as a recordable failure.
        $this->failureRecorder->expects($this->never())
            ->method('recordFailure');

        $this->orderRepository->expects($this->never())
            ->method('save');

        $this->logger->expects($this->atLeastOnce())
            ->method('error');

        $this->exportOrderConsumer->consume(json_encode(['magento_order_id' => $magentoOrderId]));
    }

    public function testConsumeUpdate()
    {
        $magentoOrderId = 123;
        $magentoQuoteId = 456;
        $request = ['request', 'customerid' => 999];
        $activeCampaignId = 789;
        $response = ['ecomOrder' => ['id' => $activeCampaignId]];

        $this->magentoOrderRepository->expects($this->once())
            ->method('get')
            ->with($magentoOrderId)
            ->willReturn($this->magentoOrder);

        $this->magentoOrder->expects($this->once())
            ->method('getQuoteId')
            ->willReturn($magentoQuoteId);

        $this->magentoOrder->expects($this->once())
            ->method('getEntityId')
            ->willReturn($magentoOrderId);

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

        $this->order->expects($this->once())
            ->method('setMagentoOrderId')
            ->with($magentoOrderId)
            ->willReturnSelf();

        $this->failureRecorder->expects($this->once())
            ->method('recordSuccess')
            ->with($this->order);

        $this->orderRepository->expects($this->once())
            ->method('save')
            ->with($this->order);

        $this->exportOrderConsumer->consume(json_encode(['magento_order_id' => $magentoOrderId]));
    }

    public function testEmptyBodyDoesNotStrand()
    {
        $magentoOrderId = 123;
        $magentoQuoteId = 456;
        $request = ['request', 'customerid' => 999];

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
            ->willReturn(['ecomOrder' => []]);

        $this->order->expects($this->never())
            ->method('setActiveCampaignId');

        $this->failureRecorder->expects($this->once())
            ->method('recordFailure')
            ->with($this->order, 'empty_response', null);

        $this->orderRepository->expects($this->once())
            ->method('save')
            ->with($this->order);

        $this->logger->expects($this->atLeastOnce())
            ->method('error');

        $this->exportOrderConsumer->consume(json_encode(['magento_order_id' => $magentoOrderId]));
    }

    public function testConsumeCreate()
    {
        $magentoOrderId = 123;
        $magentoQuoteId = 456;
        $request = ['request', 'customerid' => 999];
        $activeCampaignId = 789;
        $response = ['ecomOrder' => ['id' => $activeCampaignId]];

        $this->magentoOrderRepository->expects($this->once())
            ->method('get')
            ->with($magentoOrderId)
            ->willReturn($this->magentoOrder);

        $this->magentoOrder->expects($this->once())
            ->method('getQuoteId')
            ->willReturn($magentoQuoteId);

        $this->magentoOrder->expects($this->once())
            ->method('getEntityId')
            ->willReturn($magentoOrderId);

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

        $this->order->expects($this->once())
            ->method('setMagentoOrderId')
            ->with($magentoOrderId)
            ->willReturnSelf();

        $this->failureRecorder->expects($this->once())
            ->method('recordSuccess')
            ->with($this->order);

        $this->orderRepository->expects($this->once())
            ->method('save')
            ->with($this->order);

        $this->exportOrderConsumer->consume(json_encode(['magento_order_id' => $magentoOrderId]));
    }

    public function testConsumeCreateNonPositiveIdDoesNotSave()
    {
        $magentoOrderId = 123;
        $magentoQuoteId = 456;
        $request = ['request', 'customerid' => 999];
        $response = ['ecomOrder' => ['id' => 0]];

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

        $this->order->expects($this->never())
            ->method('setActiveCampaignId');

        $this->failureRecorder->expects($this->once())
            ->method('recordFailure')
            ->with($this->order, 'empty_response', null);

        $this->orderRepository->expects($this->once())
            ->method('save')
            ->with($this->order);

        $this->logger->expects($this->atLeastOnce())
            ->method('error');

        $this->exportOrderConsumer->consume(json_encode(['magento_order_id' => $magentoOrderId]));
    }

    public function testConsumeDuplicateNonPositiveIdDoesNotSave()
    {
        $magentoOrderId = 123;
        $magentoQuoteId = 456;
        $request = ['externalid' => 'EXT-1', 'customerid' => 999];
        $responseErrors = [['code' => 'duplicate']];

        $this->magentoOrderRepository->expects($this->once())
            ->method('get')
            ->with($magentoOrderId)
            ->willReturn($this->magentoOrder);

        $this->magentoOrder->expects($this->atLeastOnce())
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

        $this->client->expects($this->atLeastOnce())
            ->method('getOrderApi')
            ->willReturn($this->orderApi);

        /** @var MockObject|UnprocessableEntityHttpException $unprocessableEntityHttpException */
        $unprocessableEntityHttpException = $this->createMock(UnprocessableEntityHttpException::class);

        $this->orderApi->expects($this->once())
            ->method('create')
            ->with(['ecomOrder' => $request])
            ->willThrowException($unprocessableEntityHttpException);

        $unprocessableEntityHttpException->expects($this->atLeastOnce())
            ->method('getResponseErrors')
            ->willReturn($responseErrors);

        /** @var MockObject|PageInterface $page */
        $page = $this->createMock(PageInterface::class);
        $page->expects($this->atLeastOnce())
            ->method('getItems')
            ->willReturn([['id' => 0]]);

        $this->orderApi->expects($this->once())
            ->method('listPerPage')
            ->with(1, 0, ['filters' => ['externalid' => 'EXT-1']])
            ->willReturn($page);

        $this->order->expects($this->never())
            ->method('setActiveCampaignId');

        $this->failureRecorder->expects($this->once())
            ->method('recordFailure')
            ->with($this->order, 'duplicate', $this->anything());

        $this->orderRepository->expects($this->once())
            ->method('save')
            ->with($this->order);

        $this->logger->expects($this->atLeastOnce())
            ->method('error');

        $this->exportOrderConsumer->consume(json_encode(['magento_order_id' => $magentoOrderId]));
    }

    public function testOrderWithUnsyncedCustomerRepublishesCustomerAndDefers()
    {
        $magentoOrderId = 123;
        $magentoQuoteId = 456;
        $magentoCustomerId = 42;
        $request = ['request', 'customerid' => null];

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

        $this->magentoOrder->expects($this->once())
            ->method('getCustomerIsGuest')
            ->willReturn(false);

        $this->magentoOrder->expects($this->once())
            ->method('getCustomerId')
            ->willReturn($magentoCustomerId);

        $publishedTopics = [];
        $this->publisher->expects($this->exactly(2))
            ->method('publish')
            ->willReturnCallback(function (string $topic, string $body) use (&$publishedTopics) {
                $publishedTopics[$topic] = json_decode($body, true);
            });

        // The order must NOT be sent to ActiveCampaign.
        $this->client->expects($this->never())
            ->method('getOrderApi');

        $this->order->expects($this->never())
            ->method('setActiveCampaignId');

        $this->orderRepository->expects($this->never())
            ->method('save');

        $this->logger->expects($this->once())
            ->method('info');

        $this->exportOrderConsumer->consume(json_encode(['magento_order_id' => $magentoOrderId]));

        $this->assertArrayHasKey(Topics::CUSTOMER_CUSTOMER_EXPORT, $publishedTopics);
        $this->assertSame(
            ['magento_customer_id' => $magentoCustomerId],
            $publishedTopics[Topics::CUSTOMER_CUSTOMER_EXPORT]
        );

        $this->assertArrayHasKey(Topics::SALES_ORDER_EXPORT, $publishedTopics);
        $this->assertSame(
            ['magento_order_id' => $magentoOrderId, 'deferred_count' => 1],
            $publishedTopics[Topics::SALES_ORDER_EXPORT]
        );
    }

    public function testOrderWithUnsyncedGuestRepublishesGuestAndDefers()
    {
        $magentoOrderId = 123;
        $magentoQuoteId = 456;
        $request = ['request', 'customerid' => null];

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

        $this->magentoOrder->expects($this->once())
            ->method('getCustomerIsGuest')
            ->willReturn(true);

        $this->magentoOrder->method('getCustomerFirstname')->willReturn('Jane');
        $this->magentoOrder->method('getCustomerLastname')->willReturn('Doe');
        $this->magentoOrder->method('getCustomerEmail')->willReturn('jane@example.com');

        $publishedTopics = [];
        $this->publisher->expects($this->exactly(2))
            ->method('publish')
            ->willReturnCallback(function (string $topic, string $body) use (&$publishedTopics) {
                $publishedTopics[$topic] = json_decode($body, true);
            });

        $this->client->expects($this->never())
            ->method('getOrderApi');

        $this->orderRepository->expects($this->never())
            ->method('save');

        $this->logger->expects($this->once())
            ->method('info');

        $this->exportOrderConsumer->consume(json_encode(['magento_order_id' => $magentoOrderId]));

        $this->assertArrayHasKey(Topics::GUEST_CUSTOMER_EXPORT, $publishedTopics);
        $this->assertSame(
            [
                'magento_customer_id' => null,
                'customer_is_guest'   => true,
                'customer_data'       => [
                    'firstname' => 'Jane',
                    'lastname'  => 'Doe',
                    'email'     => 'jane@example.com',
                ],
            ],
            $publishedTopics[Topics::GUEST_CUSTOMER_EXPORT]
        );

        $this->assertArrayHasKey(Topics::SALES_ORDER_EXPORT, $publishedTopics);
        $this->assertSame(
            ['magento_order_id' => $magentoOrderId, 'deferred_count' => 1],
            $publishedTopics[Topics::SALES_ORDER_EXPORT]
        );
    }

    public function testBuildFailureDoesNotStrand()
    {
        $magentoOrderId = 123;
        $magentoQuoteId = 456;

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
            ->willThrowException(new \RuntimeException('builder boom'));

        // No API request, no publishing, no save.
        $this->client->expects($this->never())
            ->method('getOrderApi');

        $this->publisher->expects($this->never())
            ->method('publish');

        $this->order->expects($this->never())
            ->method('setActiveCampaignId');

        $this->failureRecorder->expects($this->once())
            ->method('recordFailure')
            ->with($this->order, 'builder_error', $this->anything());

        $this->orderRepository->expects($this->once())
            ->method('save')
            ->with($this->order);

        $this->logger->expects($this->atLeastOnce())
            ->method('error');

        $this->exportOrderConsumer->consume(json_encode(['magento_order_id' => $magentoOrderId]));
    }

    public function testOrderDeferralCapReachedSkips()
    {
        $magentoOrderId = 123;
        $magentoQuoteId = 456;
        $request = ['request', 'customerid' => null];

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

        // Cap reached: nothing republished, order not sent.
        $this->publisher->expects($this->never())
            ->method('publish');

        $this->client->expects($this->never())
            ->method('getOrderApi');

        $this->order->expects($this->never())
            ->method('setActiveCampaignId');

        $this->orderRepository->expects($this->never())
            ->method('save');

        $this->logger->expects($this->once())
            ->method('error');

        $this->exportOrderConsumer->consume(
            json_encode(['magento_order_id' => $magentoOrderId, 'deferred_count' => 1])
        );
    }

    public function testConsumeHaltsWhenBackoffShouldHalt()
    {
        $this->backoffState->expects($this->once())
            ->method('shouldHalt')
            ->willReturn(true);

        $this->logger->expects($this->once())
            ->method('warning');

        // Nothing else happens: no order lookup, no API call.
        $this->magentoOrderRepository->expects($this->never())
            ->method('get');

        $this->client->expects($this->never())
            ->method('getOrderApi');

        $this->exportOrderConsumer->consume(json_encode(['magento_order_id' => 123]));
    }

    public function testConsume503RecordsTransientFailureAndCounts503()
    {
        $magentoOrderId = 123;
        $magentoQuoteId = 456;
        $request = ['request', 'customerid' => 999];

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
            ->willThrowException($this->httpExceptionWithCode(503));

        // 503 increments the consecutive counter for the process-wide backoff.
        $this->backoffState->expects($this->once())
            ->method('record503');

        // 5xx is transient: recordFailure is called with $transient = true so a
        // ceiling can never dead-letter a 503.
        $this->failureRecorder->expects($this->once())
            ->method('recordFailure')
            ->with($this->order, 'http_error', $this->anything(), true);

        $this->orderRepository->expects($this->once())
            ->method('save')
            ->with($this->order);

        $this->exportOrderConsumer->consume(json_encode(['magento_order_id' => $magentoOrderId]));
    }

    public function testConsume500IsTransientButDoesNotCount503()
    {
        $magentoOrderId = 123;
        $magentoQuoteId = 456;
        $request = ['request', 'customerid' => 999];

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
            ->willThrowException($this->httpExceptionWithCode(500));

        // 500 is transient but not a 503, so the 503 backoff counter is untouched.
        $this->backoffState->expects($this->never())
            ->method('record503');

        $this->failureRecorder->expects($this->once())
            ->method('recordFailure')
            ->with($this->order, 'http_error', $this->anything(), true);

        $this->orderRepository->expects($this->once())
            ->method('save')
            ->with($this->order);

        $this->exportOrderConsumer->consume(json_encode(['magento_order_id' => $magentoOrderId]));
    }

    public function testConsumeSwallowsUnexpectedThrowableAndRecordsFailure()
    {
        $magentoOrderId = 123;
        $magentoQuoteId = 456;
        $request = ['request', 'customerid' => 999];

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

        $this->order->expects($this->atLeastOnce())
            ->method('getId')
            ->willReturn(77);

        // An unexpected, non-Http throwable from a body dependency.
        $this->client->expects($this->once())
            ->method('getOrderApi')
            ->willThrowException(new \RuntimeException('boom'));

        $this->failureRecorder->expects($this->once())
            ->method('recordFailure')
            ->with($this->order, 'unexpected_error', 'boom');

        $this->orderRepository->expects($this->once())
            ->method('save')
            ->with($this->order);

        $this->logger->expects($this->atLeastOnce())
            ->method('error')
            ->with($this->logicalAnd(
                $this->stringContains('entity=order'),
                $this->stringContains('local_id=77'),
                $this->stringContains('magento_id=' . $magentoOrderId),
                $this->stringContains('code=unexpected_error')
            ));

        // Must NOT propagate.
        $this->exportOrderConsumer->consume(json_encode(['magento_order_id' => $magentoOrderId]));
    }

    public function testConsume4xxIsPermanent()
    {
        $magentoOrderId = 123;
        $magentoQuoteId = 456;
        $request = ['request', 'customerid' => 999];

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
            ->willThrowException($this->httpExceptionWithCode(404));

        $this->backoffState->expects($this->never())
            ->method('record503');

        // 4xx is permanent: $transient = false (counts toward the dead-letter ceiling).
        $this->failureRecorder->expects($this->once())
            ->method('recordFailure')
            ->with($this->order, 'http_error', $this->anything(), false);

        $this->orderRepository->expects($this->once())
            ->method('save')
            ->with($this->order);

        $this->exportOrderConsumer->consume(json_encode(['magento_order_id' => $magentoOrderId]));
    }

}
