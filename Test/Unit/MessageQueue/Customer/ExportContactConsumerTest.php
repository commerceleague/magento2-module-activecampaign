<?php
declare(strict_types=1);
/**
 */

namespace CommerceLeague\ActiveCampaign\Test\Unit\MessageQueue\Customer;

use CommerceLeague\ActiveCampaign\Api\ContactRepositoryInterface;
use CommerceLeague\ActiveCampaign\Api\Data\ContactInterface;
use CommerceLeague\ActiveCampaign\Gateway\Request\ContactBuilder as ContactRequestBuilder;
use CommerceLeague\ActiveCampaign\Logger\Logger;
use CommerceLeague\ActiveCampaign\MessageQueue\Customer\ExportContactConsumer;
use CommerceLeague\ActiveCampaignApi\Api\ContactApiResourceInterface;
use CommerceLeague\ActiveCampaignApi\Exception\HttpException;
use Magento\Customer\Api\CustomerRepositoryInterface as MagentoCustomerRepositoryInterface;
use Magento\Customer\Api\Data\CustomerInterface as MagentoCustomerInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Phrase;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use CommerceLeague\ActiveCampaign\Gateway\Client;

class ExportContactConsumerTest extends TestCase
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
     * @var MockObject|ContactRepositoryInterface
     */
    protected $contactRepository;

    /**
     * @var MockObject|ContactRequestBuilder
     */
    protected $contactRequestBuilder;

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
     * @var MockObject|MagentoCustomerInterface
     */
    protected $magentoCustomer;

    /**
     * @var ExportContactConsumer
     */
    protected $exportContactConsumer;

    protected function setUp()
    {
        $this->magentoCustomerRepository = $this->createMock(MagentoCustomerRepositoryInterface::class);
        $this->logger = $this->createMock(Logger::class);
        $this->contactRepository = $this->createMock(ContactRepositoryInterface::class);
        $this->contactRequestBuilder = $this->createMock(ContactRequestBuilder::class);
        $this->client = $this->createMock(Client::class);
        $this->contact = $this->createMock(ContactInterface::class);
        $this->contactApi = $this->createMock(ContactApiResourceInterface::class);
        $this->magentoCustomer = $this->createMock(MagentoCustomerInterface::class);

        $this->exportContactConsumer = new ExportContactConsumer(
            $this->magentoCustomerRepository,
            $this->logger,
            $this->contactRepository,
            $this->contactRequestBuilder,
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

        $this->contactRepository->expects($this->never())
            ->method('getOrCreateByEmail');

        $this->exportContactConsumer->consume(json_encode(['magento_customer_id' => $magentoCustomerId]));
    }

    public function testConsumeApiRequestException()
    {
        $magentoCustomerId = 123;
        $email = 'example@example.com';
        $request = ['request'];

        $this->magentoCustomerRepository->expects($this->once())
            ->method('getById')
            ->with($magentoCustomerId)
            ->willReturn($this->magentoCustomer);

        $this->magentoCustomer->expects($this->once())
            ->method('getEmail')
            ->willReturn($email);

        $this->contactRepository->expects($this->once())
            ->method('getOrCreateByEmail')
            ->with($email)
            ->willReturn($this->contact);

        $this->contactRequestBuilder->expects($this->once())
            ->method('buildWithMagentoCustomer')
            ->willReturn($request);

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

        $this->exportContactConsumer->consume(json_encode(['magento_customer_id' => $magentoCustomerId]));
    }

    public function testConsume()
    {
        $magentoCustomerId = 123;
        $email = 'example@example.com';
        $request = ['request'];
        $activeCampaignId = 456;
        $response = ['contact' => ['id' => $activeCampaignId]];

        $this->magentoCustomerRepository->expects($this->once())
            ->method('getById')
            ->with($magentoCustomerId)
            ->willReturn($this->magentoCustomer);

        $this->magentoCustomer->expects($this->once())
            ->method('getEmail')
            ->willReturn($email);

        $this->contactRepository->expects($this->once())
            ->method('getOrCreateByEmail')
            ->with($email)
            ->willReturn($this->contact);

        $this->contactRequestBuilder->expects($this->once())
            ->method('buildWithMagentoCustomer')
            ->with($this->magentoCustomer)
            ->willReturn($request);

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

        $this->logger->expects($this->never())
            ->method('error');

        $this->exportContactConsumer->consume(json_encode(['magento_customer_id' => $magentoCustomerId]));
    }
}
