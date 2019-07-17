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
use CommerceLeague\ActiveCampaignApi\Api\OrderApiResourceInterface;
use CommerceLeague\ActiveCampaignApi\Exception\HttpException;
use CommerceLeague\ActiveCampaignApi\Exception\UnprocessableEntityHttpException;
use Magento\Quote\Model\Quote;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Magento\Quote\Model\QuoteFactory;

class ExportAbandonedCartConsumerTest extends TestCase
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
     * @var ExportAbandonedCartConsumer
     */
    protected $exportAbandonedCartConsumer;

    protected function setUp()
    {
        $this->quoteFactory = $this->getMockBuilder(QuoteFactory::class)
            ->disableOriginalConstructor()
            ->setMethods(['create'])
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

        $this->exportAbandonedCartConsumer = new ExportAbandonedCartConsumer(
            $this->quoteFactory,
            $this->logger,
            $this->orderRepository,
            $this->abandonedCartRequestBuilder,
            $this->client
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

        /** @var MockObject|UnprocessableEntityHttpException $unprocessableEntityHttpException */
        $unprocessableEntityHttpException = $this->createMock(UnprocessableEntityHttpException::class);

        $this->orderApi->expects($this->once())
            ->method('create')
            ->with(['ecomOrder' => $request])
            ->willThrowException($unprocessableEntityHttpException);

        $this->logger->expects($this->exactly(2))
            ->method('error');

        $unprocessableEntityHttpException->expects($this->once())
            ->method('getResponseErrors')
            ->willReturn($responseErrors);

        $this->logger->expects($this->at(1))
            ->method('error')
            ->with(print_r($responseErrors, true));

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

        $this->orderRepository->expects($this->once())
            ->method('save')
            ->with($this->order);

        $this->exportAbandonedCartConsumer->consume(json_encode(['quote_id' => $quoteId]));
    }
}
