<?php
declare(strict_types=1);
/**
 */

namespace CommerceLeague\ActiveCampaign\Test\Unit\MessageQueue\Quote;

use CommerceLeague\ActiveCampaign\Api\Data\OrderInterface;
use CommerceLeague\ActiveCampaign\Api\OrderRepositoryInterface;
use CommerceLeague\ActiveCampaign\Gateway\Client;
use CommerceLeague\ActiveCampaign\Gateway\Request\AbandonedCartBuilder as AbandonedCartRequestBuilder;
use CommerceLeague\ActiveCampaign\Logger\Logger;
use CommerceLeague\ActiveCampaign\MessageQueue\Quote\ExportAbandonedCartConsumer;
use CommerceLeague\ActiveCampaign\Model\Export\FailureRecorder;
use CommerceLeague\ActiveCampaign\Test\Unit\AbstractTestCase;
use CommerceLeague\ActiveCampaignApi\Api\OrderApiResourceInterface;
use CommerceLeague\ActiveCampaignApi\Exception\HttpException;
use CommerceLeague\ActiveCampaignApi\Exception\UnprocessableEntityHttpException;
use CommerceLeague\ActiveCampaignApi\Paginator\PageInterface;
use Magento\Quote\Model\Quote;
use Magento\Quote\Model\QuoteFactory;
use PHPUnit\Framework\MockObject\MockObject;

class ExportAbandonedCartConsumerTest extends AbstractTestCase
{

    /**
     * @var MockObject|QuoteFactory
     */
    protected $quoteFactory;

    /**
     * @var MockObject|Quote
     */
    protected $quote;

    /**
     * @var MockObject|Logger
     */
    protected $logger;

    /**
     * @var MockObject|OrderRepositoryInterface
     */
    protected $orderRepository;

    /**
     * @var MockObject|AbandonedCartRequestBuilder
     */
    protected $abandonedCartRequestBuilder;

    /**
     * @var MockObject|Client
     */
    protected $client;

    /**
     * @var MockObject|OrderApiResourceInterface
     */
    protected $orderApi;

    /**
     * @var MockObject|OrderInterface
     */
    protected $order;

    /**
     * @var MockObject|FailureRecorder
     */
    protected $failureRecorder;

    /**
     * @var ExportAbandonedCartConsumer
     */
    protected $exportAbandonedCartConsumer;

    protected function setUp(): void
    {
        $this->quoteFactory = $this->getMockBuilder(QuoteFactory::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['create'])
            ->getMock();

        $this->quote = $this->createMock(Quote::class);

        $this->quoteFactory->expects($this->any())
            ->method('create')
            ->willReturn($this->quote);

        $this->logger = $this->createMock(Logger::class);
        $this->orderRepository = $this->createMock(OrderRepositoryInterface::class);
        $this->abandonedCartRequestBuilder = $this->createMock(AbandonedCartRequestBuilder::class);
        $this->client = $this->createMock(Client::class);
        $this->orderApi = $this->createMock(OrderApiResourceInterface::class);
        $this->order = $this->createMock(OrderInterface::class);
        $this->failureRecorder = $this->createMock(FailureRecorder::class);

        $this->exportAbandonedCartConsumer = new ExportAbandonedCartConsumer(
            $this->quoteFactory,
            $this->logger,
            $this->orderRepository,
            $this->abandonedCartRequestBuilder,
            $this->client,
            $this->failureRecorder
        );
    }

    public function testConsumeWithAbsentQuote()
    {
        $quoteId = 123;

        $this->quote->expects($this->once())
            ->method('loadByIdWithoutStore')
            ->with(123)
            ->willReturn($this->quote);

        $this->quote->expects($this->once())
            ->method('getId')
            ->willReturn(null);

        $this->logger->expects($this->once())
            ->method('error')
            ->with(__('The Quote with the "%1" ID doesn\'t exist', $quoteId));

        $this->orderRepository->expects($this->never())
            ->method('getOrCreateByMagentoQuoteId');

        $this->exportAbandonedCartConsumer->consume(
            json_encode(['quote_id' => $quoteId])
        );
    }

    public function testConsumeApiHttpException()
    {
        $quoteId = 123;
        $request = ['request'];

        $this->quote->expects($this->once())
            ->method('loadByIdWithoutStore')
            ->with(123)
            ->willReturn($this->quote);

        $this->quote->expects($this->any())
            ->method('getId')
            ->willReturn($quoteId);

        $this->orderRepository->expects($this->once())
            ->method('getOrCreateByMagentoQuoteId')
            ->with($quoteId)
            ->willReturn($this->order);

        $this->abandonedCartRequestBuilder->expects($this->once())
            ->method('build')
            ->with($this->quote)
            ->willReturn($request);

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

        $this->exportAbandonedCartConsumer->consume(json_encode(['quote_id' => $quoteId]));
    }

    public function testConsumeApiUnprocessableEntityHttpExceptionException()
    {
        $quoteId = 123;
        $request = ['request'];
        $responseErrors = ['first error', 'second error'];

        $this->quote->expects($this->once())
            ->method('loadByIdWithoutStore')
            ->with(123)
            ->willReturn($this->quote);

        $this->quote->expects($this->any())
            ->method('getId')
            ->willReturn($quoteId);

        $this->orderRepository->expects($this->once())
            ->method('getOrCreateByMagentoQuoteId')
            ->with($quoteId)
            ->willReturn($this->order);

        $this->abandonedCartRequestBuilder->expects($this->once())
            ->method('build')
            ->with($this->quote)
            ->willReturn($request);

        $this->client->expects($this->once())
            ->method('getOrderApi')
            ->willReturn($this->orderApi);

        $this->unprocessableEntityHttpException(
            $this->orderApi, $this->logger, $request, $responseErrors, 'ecomOrder', 'create');


        $this->order->expects($this->never())
            ->method('setActiveCampaignId');

        $this->exportAbandonedCartConsumer->consume(json_encode(['quote_id' => $quoteId]));
    }

    public function testConsume()
    {
        $quoteId = 123;
        $request = ['request'];
        $activeCampaignId = 789;
        $response = ['ecomOrder' => ['id' => $activeCampaignId]];

        $this->quote->expects($this->once())
            ->method('loadByIdWithoutStore')
            ->with(123)
            ->willReturn($this->quote);

        $this->quote->expects($this->any())
            ->method('getId')
            ->willReturn($quoteId);

        $this->orderRepository->expects($this->once())
            ->method('getOrCreateByMagentoQuoteId')
            ->with($quoteId)
            ->willReturn($this->order);

        $this->abandonedCartRequestBuilder->expects($this->once())
            ->method('build')
            ->with($this->quote)
            ->willReturn($request);

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

        $this->failureRecorder->expects($this->once())
            ->method('recordSuccess')
            ->with($this->order);

        $this->orderRepository->expects($this->once())
            ->method('save')
            ->with($this->order);

        $this->exportAbandonedCartConsumer->consume(json_encode(['quote_id' => $quoteId]));
    }

    public function testEmptyBodyDoesNotStrand()
    {
        $quoteId = 123;
        $request = ['request'];

        $this->quote->expects($this->once())
            ->method('loadByIdWithoutStore')
            ->with(123)
            ->willReturn($this->quote);

        $this->quote->expects($this->any())
            ->method('getId')
            ->willReturn($quoteId);

        $this->orderRepository->expects($this->once())
            ->method('getOrCreateByMagentoQuoteId')
            ->with($quoteId)
            ->willReturn($this->order);

        $this->abandonedCartRequestBuilder->expects($this->once())
            ->method('build')
            ->with($this->quote)
            ->willReturn($request);

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

        $this->exportAbandonedCartConsumer->consume(json_encode(['quote_id' => $quoteId]));
    }

    public function testBuildFailureDoesNotStrand()
    {
        $quoteId = 123;

        $this->quote->expects($this->once())
            ->method('loadByIdWithoutStore')
            ->with(123)
            ->willReturn($this->quote);

        $this->quote->expects($this->any())
            ->method('getId')
            ->willReturn($quoteId);

        $this->orderRepository->expects($this->once())
            ->method('getOrCreateByMagentoQuoteId')
            ->with($quoteId)
            ->willReturn($this->order);

        $this->abandonedCartRequestBuilder->expects($this->once())
            ->method('build')
            ->with($this->quote)
            ->willThrowException(new \RuntimeException('builder boom'));

        // No API request, but the failed attempt is recorded and persisted.
        $this->client->expects($this->never())
            ->method('getOrderApi');

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

        $this->exportAbandonedCartConsumer->consume(json_encode(['quote_id' => $quoteId]));
    }

    public function testConsumeDuplicateResolvesAndSaves()
    {
        $quoteId = 123;
        $resolvedId = 555;
        $request = ['externalcheckoutid' => $quoteId];
        $responseErrors = [['code' => 'duplicate']];

        $this->quote->expects($this->once())
            ->method('loadByIdWithoutStore')
            ->with(123)
            ->willReturn($this->quote);

        $this->quote->expects($this->any())
            ->method('getId')
            ->willReturn($quoteId);

        $this->orderRepository->expects($this->once())
            ->method('getOrCreateByMagentoQuoteId')
            ->with($quoteId)
            ->willReturn($this->order);

        $this->abandonedCartRequestBuilder->expects($this->once())
            ->method('build')
            ->with($this->quote)
            ->willReturn($request);

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
            ->with(1, 0, ['filters' => ['externalcheckoutid' => $quoteId]])
            ->willReturn($page);

        $this->order->expects($this->once())
            ->method('setActiveCampaignId')
            ->with($resolvedId)
            ->willReturnSelf();

        $this->failureRecorder->expects($this->once())
            ->method('recordSuccess')
            ->with($this->order);

        $this->orderRepository->expects($this->once())
            ->method('save')
            ->with($this->order);

        $this->exportAbandonedCartConsumer->consume(json_encode(['quote_id' => $quoteId]));
    }

    public function testConsumeDuplicateLookupEmptyDoesNotFatal()
    {
        $quoteId = 123;
        $request = ['externalcheckoutid' => $quoteId];
        $responseErrors = [['code' => 'duplicate']];

        $this->quote->expects($this->once())
            ->method('loadByIdWithoutStore')
            ->with(123)
            ->willReturn($this->quote);

        $this->quote->expects($this->any())
            ->method('getId')
            ->willReturn($quoteId);

        $this->orderRepository->expects($this->once())
            ->method('getOrCreateByMagentoQuoteId')
            ->with($quoteId)
            ->willReturn($this->order);

        $this->abandonedCartRequestBuilder->expects($this->once())
            ->method('build')
            ->with($this->quote)
            ->willReturn($request);

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
            ->with(1, 0, ['filters' => ['externalcheckoutid' => $quoteId]])
            ->willReturn($page);

        $this->order->expects($this->never())
            ->method('setActiveCampaignId');

        $this->orderRepository->expects($this->never())
            ->method('save');

        $this->logger->expects($this->atLeastOnce())
            ->method('error');

        $this->exportAbandonedCartConsumer->consume(json_encode(['quote_id' => $quoteId]));
    }
}
