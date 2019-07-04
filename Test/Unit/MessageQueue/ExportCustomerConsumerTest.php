<?php
declare(strict_types=1);
/**
 */

namespace CommerceLeague\ActiveCampaign\Test\Unit\MessageQueue;

use CommerceLeague\ActiveCampaign\Api\CustomerRepositoryInterface;
use CommerceLeague\ActiveCampaign\Api\Data\CustomerInterface;
use CommerceLeague\ActiveCampaign\Gateway\Client;
use CommerceLeague\ActiveCampaign\Logger\Logger;
use CommerceLeague\ActiveCampaign\MessageQueue\ExportCustomerConsumer;
use CommerceLeague\ActiveCampaignApi\Api\CustomerApiResourceInterface;
use CommerceLeague\ActiveCampaignApi\Exception\HttpException;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class ExportCustomerConsumerTest extends TestCase
{
    /**
     * @var MockObject|CustomerRepositoryInterface
     */
    protected $customerRepository;

    /**
     * @var MockObject|Logger
     */
    protected $logger;

    /**
     * @var MockObject|Client
     */
    protected $client;

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
        $this->customerRepository = $this->createMock(CustomerRepositoryInterface::class);
        $this->logger = $this->createMock(Logger::class);
        $this->client = $this->createMock(Client::class);
        $this->customer = $this->createMock(CustomerInterface::class);
        $this->customerApi = $this->createMock(CustomerApiResourceInterface::class);

        $this->exportCustomerConsumer = new ExportCustomerConsumer(
            $this->customerRepository,
            $this->logger,
            $this->client
        );
    }

    public function testConsumeApiResponseException()
    {
        $magentoCustomerId = 123;

        $this->customerRepository->expects($this->once())
            ->method('getOrCreateByMagentoCustomerId')
            ->with($magentoCustomerId)
            ->willReturn($this->customer);

        $this->client->expects($this->once())
            ->method('getCustomerApi')
            ->willReturn($this->customerApi);

        /** @var MockObject|HttpException $httpException */
        $httpException = $this->createMock(HttpException::class);

        $this->customerApi->expects($this->once())
            ->method('create')
            ->with(['ecomCustomer' => []])
            ->willThrowException($httpException);

        $this->logger->expects($this->once())
            ->method('error');

        $this->customer->expects($this->never())
            ->method('setActiveCampaignId');

        $this->exportCustomerConsumer->consume(
            json_encode(['magento_customer_id' => $magentoCustomerId, 'request' => []])
        );
    }

    public function testConsumeUpdate()
    {
        $magentoCustomerId = 123;
        $activeCampaignId = 123;
        $response = [
            'ecomCustomer' => ['id' => $activeCampaignId]
        ];

        $this->customerRepository->expects($this->once())
            ->method('getOrCreateByMagentoCustomerId')
            ->with($magentoCustomerId)
            ->willReturn($this->customer);

        $this->client->expects($this->once())
            ->method('getCustomerApi')
            ->willReturn($this->customerApi);

        $this->customer->expects($this->once())
            ->method('getActiveCampaignId')
            ->willReturn($activeCampaignId);

        $this->customerApi->expects($this->once())
            ->method('update')
            ->with($activeCampaignId, ['ecomCustomer' => []])
            ->willReturn($response);

        $this->customer->expects($this->once())
            ->method('setActiveCampaignId')
            ->with($activeCampaignId)
            ->willReturnSelf();

        $this->customerRepository->expects($this->once())
            ->method('save')
            ->with($this->customer);

        $this->exportCustomerConsumer->consume(
            json_encode(['magento_customer_id' => $magentoCustomerId, 'request' => []])
        );
    }

    public function testConsumeCreate()
    {
        $magentoCustomerId = 123;
        $activeCampaignId = 123;
        $response = [
            'ecomCustomer' => ['id' => $activeCampaignId]
        ];

        $this->customerRepository->expects($this->once())
            ->method('getOrCreateByMagentoCustomerId')
            ->with($magentoCustomerId)
            ->willReturn($this->customer);

        $this->client->expects($this->once())
            ->method('getCustomerApi')
            ->willReturn($this->customerApi);

        $this->customer->expects($this->once())
            ->method('getActiveCampaignId')
            ->willReturn(null);

        $this->customerApi->expects($this->once())
            ->method('create')
            ->with(['ecomCustomer' => []])
            ->willReturn($response);

        $this->customer->expects($this->once())
            ->method('setActiveCampaignId')
            ->with($activeCampaignId)
            ->willReturnSelf();

        $this->customerRepository->expects($this->once())
            ->method('save')
            ->with($this->customer);

        $this->exportCustomerConsumer->consume(
            json_encode(['magento_customer_id' => $magentoCustomerId, 'request' => []])
        );
    }
}
