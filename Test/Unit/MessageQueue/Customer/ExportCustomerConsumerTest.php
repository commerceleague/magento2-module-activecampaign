<?php
declare(strict_types=1);
/**
 */

namespace CommerceLeague\ActiveCampaign\Test\Unit\MessageQueue\Customer;

use CommerceLeague\ActiveCampaign\Api\CustomerRepositoryInterface;
use CommerceLeague\ActiveCampaign\Api\Data\CustomerInterface;
use CommerceLeague\ActiveCampaign\Gateway\Client;
use CommerceLeague\ActiveCampaign\Gateway\Request\CustomerBuilder as CustomerRequestBuilder;
use CommerceLeague\ActiveCampaign\Logger\Logger;
use CommerceLeague\ActiveCampaign\MessageQueue\Customer\ExportCustomerConsumer;
use CommerceLeague\ActiveCampaignApi\Api\CustomerApiResourceInterface;
use CommerceLeague\ActiveCampaignApi\Exception\HttpException;
use CommerceLeague\ActiveCampaignApi\Exception\UnprocessableEntityHttpException;
use Magento\Customer\Api\CustomerRepositoryInterface as MagentoCustomerRepositoryInterface;
use Magento\Customer\Api\Data\CustomerInterface as MagentoCustomerInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Phrase;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class ExportCustomerConsumerTest extends TestCase
{
    /**
     * @var MockObject|MagentoCustomerRepositoryInterface
     */
    protected $magentoCustomerRepository;

    /**
     * @var MockObject|Logger
     */
    protected $logger;

    /**
     * @var MockObject|CustomerRepositoryInterface
     */
    protected $customerRepository;

    /**
     * @var MockObject|CustomerRequestBuilder
     */
    protected $customerRequestBuilder;

    /**
     * @var MockObject|Client
     */
    protected $client;

    /**
     * @var MockObject|MagentoCustomerInterface
     */
    protected $magentoCustomer;

    /**
     * @var MockObject|CustomerInterface
     */
    protected $customer;

    /**
     * @var MockObject|CustomerApiResourceInterface
     */
    protected $customerApi;

    /**
     * @var ExportCustomerConsumer
     */
    protected $exportCustomerConsumer;

    protected function setUp()
    {
        $this->magentoCustomerRepository = $this->createMock(MagentoCustomerRepositoryInterface::class);
        $this->logger = $this->createMock(Logger::class);
        $this->customerRepository = $this->createMock(CustomerRepositoryInterface::class);
        $this->customerRequestBuilder = $this->createMock(CustomerRequestBuilder::class);
        $this->client = $this->createMock(Client::class);
        $this->customer = $this->createMock(CustomerInterface::class);
        $this->customerApi = $this->createMock(CustomerApiResourceInterface::class);
        $this->magentoCustomer = $this->createMock(MagentoCustomerInterface::class);

        $this->exportCustomerConsumer = new ExportCustomerConsumer(
            $this->magentoCustomerRepository,
            $this->logger,
            $this->customerRepository,
            $this->customerRequestBuilder,
            $this->client
        );
    }

    public function testConsumeWithAbsentMagentoCustomer()
    {
        $magentoCustomerId = 123;

        $exceptionMessage = 'an exception message';
        $exception = new NoSuchEntityException(new Phrase($exceptionMessage));

        $this->magentoCustomerRepository->expects($this->once())
            ->method('getById')
            ->with($magentoCustomerId)
            ->willThrowException($exception);

        $this->logger->expects($this->once())
            ->method('error')
            ->with($exceptionMessage);

        $this->customerRepository->expects($this->never())
            ->method('getOrCreateByMagentoCustomerId');

        $this->exportCustomerConsumer->consume(json_encode(['magento_customer_id' => $magentoCustomerId]));
    }

    public function testConsumeApiHttpException()
    {
        $magentoCustomerId = 123;
        $request = ['request'];

        $this->magentoCustomerRepository->expects($this->once())
            ->method('getById')
            ->with($magentoCustomerId)
            ->willReturn($this->magentoCustomer);

        $this->magentoCustomer->expects($this->once())
            ->method('getId')
            ->willReturn($magentoCustomerId);

        $this->customerRequestBuilder->expects($this->once())
            ->method('build')
            ->with($this->magentoCustomer)
            ->willReturn($request);

        $this->client->expects($this->once())
            ->method('getCustomerApi')
            ->willReturn($this->customerApi);

        /** @var MockObject|HttpException $httpException */
        $httpException = $this->createMock(HttpException::class);

        $this->customerApi->expects($this->once())
            ->method('create')
            ->with(['ecomCustomer' => $request])
            ->willThrowException($httpException);

        $this->logger->expects($this->once())
            ->method('error');

        $this->customer->expects($this->never())
            ->method('setActiveCampaignId');

        $this->exportCustomerConsumer->consume(json_encode(['magento_customer_id' => $magentoCustomerId]));
    }

    public function testConsumeApiUnprocessableEntityHttpExceptionException()
    {
        $magentoCustomerId = 123;
        $request = ['request'];
        $responseErrors = ['first error', 'second error'];

        $this->magentoCustomerRepository->expects($this->once())
            ->method('getById')
            ->with($magentoCustomerId)
            ->willReturn($this->magentoCustomer);

        $this->magentoCustomer->expects($this->once())
            ->method('getId')
            ->willReturn($magentoCustomerId);

        $this->customerRequestBuilder->expects($this->once())
            ->method('build')
            ->with($this->magentoCustomer)
            ->willReturn($request);

        $this->client->expects($this->once())
            ->method('getCustomerApi')
            ->willReturn($this->customerApi);

        /** @var MockObject|UnprocessableEntityHttpException $unprocessableEntityHttpException */
        $unprocessableEntityHttpException = $this->createMock(UnprocessableEntityHttpException::class);

        $this->customerApi->expects($this->once())
            ->method('create')
            ->with(['ecomCustomer' => $request])
            ->willThrowException($unprocessableEntityHttpException);

        $this->logger->expects($this->exactly(2))
            ->method('error');

        $unprocessableEntityHttpException->expects($this->once())
            ->method('getResponseErrors')
            ->willReturn($responseErrors);

        $this->logger->expects($this->at(1))
            ->method('error')
            ->with(print_r($responseErrors, true));

        $this->customer->expects($this->never())
            ->method('setActiveCampaignId');

        $this->exportCustomerConsumer->consume(json_encode(['magento_customer_id' => $magentoCustomerId]));
    }

    public function testConsumeUpdate()
    {
        $magentoCustomerId = 123;
        $request = ['request'];
        $activeCampaignId = 456;
        $response = ['ecomCustomer' => ['id' => $activeCampaignId]];

        $this->magentoCustomerRepository->expects($this->once())
            ->method('getById')
            ->with($magentoCustomerId)
            ->willReturn($this->magentoCustomer);

        $this->magentoCustomer->expects($this->once())
            ->method('getId')
            ->willReturn($magentoCustomerId);

        $this->customerRepository->expects($this->once())
            ->method('getOrCreateByMagentoCustomerId')
            ->willReturn($this->customer);

        $this->customerRequestBuilder->expects($this->once())
            ->method('build')
            ->with($this->magentoCustomer)
            ->willReturn($request);

        $this->customer->expects($this->once())
            ->method('getActiveCampaignId')
            ->willReturn($activeCampaignId);

        $this->client->expects($this->once())
            ->method('getCustomerApi')
            ->willReturn($this->customerApi);

        $this->customerApi->expects($this->once())
            ->method('update')
            ->with($activeCampaignId, ['ecomCustomer' => $request])
            ->willReturn($response);

        $this->customer->expects($this->once())
            ->method('setActiveCampaignId')
            ->with($activeCampaignId)
            ->willReturnSelf();

        $this->customerRepository->expects($this->once())
            ->method('save')
            ->with($this->customer);

        $this->exportCustomerConsumer->consume(json_encode(['magento_customer_id' => $magentoCustomerId]));
    }

    public function testConsumeCreate()
    {
        $magentoCustomerId = 123;
        $request = ['request'];
        $activeCampaignId = 456;
        $response = ['ecomCustomer' => ['id' => $activeCampaignId]];

        $this->magentoCustomerRepository->expects($this->once())
            ->method('getById')
            ->with($magentoCustomerId)
            ->willReturn($this->magentoCustomer);

        $this->magentoCustomer->expects($this->once())
            ->method('getId')
            ->willReturn($magentoCustomerId);

        $this->customerRepository->expects($this->once())
            ->method('getOrCreateByMagentoCustomerId')
            ->willReturn($this->customer);

        $this->customerRequestBuilder->expects($this->once())
            ->method('build')
            ->with($this->magentoCustomer)
            ->willReturn($request);

        $this->customer->expects($this->once())
            ->method('getActiveCampaignId')
            ->willReturn(null);

        $this->client->expects($this->once())
            ->method('getCustomerApi')
            ->willReturn($this->customerApi);

        $this->customerApi->expects($this->once())
            ->method('create')
            ->with(['ecomCustomer' => $request])
            ->willReturn($response);

        $this->customer->expects($this->once())
            ->method('setActiveCampaignId')
            ->with($activeCampaignId)
            ->willReturnSelf();

        $this->customerRepository->expects($this->once())
            ->method('save')
            ->with($this->customer);

        $this->exportCustomerConsumer->consume(json_encode(['magento_customer_id' => $magentoCustomerId]));
    }
}
