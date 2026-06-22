<?php
declare(strict_types=1);
/**
 */

namespace CommerceLeague\ActiveCampaign\Test\Unit\MessageQueue\Customer;

use CommerceLeague\ActiveCampaign\Api\Data\GuestCustomerInterface;
use CommerceLeague\ActiveCampaign\Gateway\Client;
use CommerceLeague\ActiveCampaign\Gateway\Request\CustomerBuilder as CustomerRequestBuilder;
use CommerceLeague\ActiveCampaign\Helper\Config;
use CommerceLeague\ActiveCampaign\Logger\Logger;
use CommerceLeague\ActiveCampaign\MessageQueue\Customer\ExportGuestCustomerConsumer;
use CommerceLeague\ActiveCampaign\Model\ActiveCampaign\GuestCustomerRepository;
use CommerceLeague\ActiveCampaign\Model\Export\BackoffState;
use CommerceLeague\ActiveCampaign\Model\Export\FailureRecorder;
use CommerceLeague\ActiveCampaign\Model\Tombstone\TombstoneRelinker;
use CommerceLeague\ActiveCampaign\Test\Unit\AbstractTestCase;
use CommerceLeague\ActiveCampaignApi\Api\CustomerApiResourceInterface;
use CommerceLeague\ActiveCampaignApi\Exception\HttpException;
use CommerceLeague\ActiveCampaignApi\Exception\UnprocessableEntityHttpException;
use CommerceLeague\ActiveCampaignApi\Paginator\PageInterface;
use PHPUnit\Framework\MockObject\MockObject;

class ExportGuestCustomerConsumerTest extends AbstractTestCase
{
    /**
     * @var MockObject|Logger
     */
    protected $logger;

    /**
     * @var MockObject|GuestCustomerRepository
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
     * @var MockObject|CustomerApiResourceInterface
     */
    protected $customerApi;

    /**
     * @var MockObject|GuestCustomerInterface
     */
    protected $guestCustomer;

    /**
     * @var MockObject|FailureRecorder
     */
    protected $failureRecorder;

    /**
     * @var MockObject|BackoffState
     */
    protected $backoffState;

    /**
     * @var MockObject|Config
     */
    protected $config;

    /**
     * @var MockObject|TombstoneRelinker
     */
    protected $tombstoneRelinker;

    /**
     * @var ExportGuestCustomerConsumer
     */
    protected $exportGuestCustomerConsumer;

    protected function setUp(): void
    {
        $this->logger = $this->createMock(Logger::class);
        $this->customerRepository = $this->createMock(GuestCustomerRepository::class);
        $this->customerRequestBuilder = $this->createMock(CustomerRequestBuilder::class);
        $this->client = $this->createMock(Client::class);
        $this->customerApi = $this->createMock(CustomerApiResourceInterface::class);
        $this->guestCustomer = $this->createMock(GuestCustomerInterface::class);
        $this->failureRecorder = $this->createMock(FailureRecorder::class);
        $this->backoffState = $this->createMock(BackoffState::class);
        $this->config = $this->createMock(Config::class);
        $this->tombstoneRelinker = $this->createMock(TombstoneRelinker::class);

        // Default OFF: zero behaviour change for existing tests.
        $this->config->method('isTombstoneSelfHealEnabled')->willReturn(false);

        $this->exportGuestCustomerConsumer = new ExportGuestCustomerConsumer(
            $this->logger,
            $this->customerRepository,
            $this->customerRequestBuilder,
            $this->client,
            $this->failureRecorder,
            $this->backoffState,
            $this->config,
            $this->tombstoneRelinker
        );
    }

    /**
     * Invoke the private performApiRequest() with the given relink result wired in.
     * The guest consume() only reaches the create branch, so the self-heal update
     * branch is exercised directly here.
     *
     * @return array<string, mixed>
     */
    private function invokePerformApiRequest(
        ExportGuestCustomerConsumer $consumer,
        GuestCustomerInterface $guestCustomer,
        array $request
    ): array {
        $method = new \ReflectionMethod(ExportGuestCustomerConsumer::class, 'performApiRequest');
        $method->setAccessible(true);

        /** @var array<string, mixed> $result */
        $result = $method->invoke($consumer, $guestCustomer, $request);

        return $result;
    }

    private function buildConsumerWithSelfHeal(bool $enabled): ExportGuestCustomerConsumer
    {
        $this->config = $this->createMock(Config::class);
        $this->config->method('isTombstoneSelfHealEnabled')->willReturn($enabled);

        return new ExportGuestCustomerConsumer(
            $this->logger,
            $this->customerRepository,
            $this->customerRequestBuilder,
            $this->client,
            $this->failureRecorder,
            $this->backoffState,
            $this->config,
            $this->tombstoneRelinker
        );
    }

    /**
     * Self-heal default OFF: the relinker is never called; a normal update happens.
     */
    public function testPerformApiRequestSelfHealDisabledUpdatesWithoutRelinker()
    {
        $request = ['email' => 'guest@example.com', 'externalid' => 'guest-5', 'connectionid' => 7];
        $activeCampaignId = 456;
        $response = ['ecomCustomer' => ['id' => $activeCampaignId]];

        $this->guestCustomer->expects($this->once())
            ->method('getActiveCampaignId')
            ->willReturn($activeCampaignId);

        $this->tombstoneRelinker->expects($this->never())
            ->method('relink');

        $this->client->expects($this->once())
            ->method('getCustomerApi')
            ->willReturn($this->customerApi);

        $this->customerApi->expects($this->once())
            ->method('update')
            ->with($activeCampaignId, ['ecomCustomer' => $request])
            ->willReturn($response);

        $consumer = $this->buildConsumerWithSelfHeal(false);

        $this->assertSame($response, $this->invokePerformApiRequest($consumer, $this->guestCustomer, $request));
    }

    /**
     * Self-heal ON + RELINKED: relinker runs once, then the normal update happens.
     */
    public function testPerformApiRequestSelfHealRelinkedThenUpdates()
    {
        $request = ['email' => 'guest@example.com', 'externalid' => 'guest-5', 'connectionid' => 7];
        $activeCampaignId = 456;
        $response = ['ecomCustomer' => ['id' => $activeCampaignId]];

        $this->guestCustomer->expects($this->once())
            ->method('getActiveCampaignId')
            ->willReturn($activeCampaignId);

        $this->tombstoneRelinker->expects($this->once())
            ->method('relink')
            ->with($activeCampaignId, 'guest@example.com', 'guest-5', true)
            ->willReturn(TombstoneRelinker::RESULT_RELINKED);

        $this->client->expects($this->once())
            ->method('getCustomerApi')
            ->willReturn($this->customerApi);

        $this->customerApi->expects($this->once())
            ->method('update')
            ->with($activeCampaignId, ['ecomCustomer' => $request])
            ->willReturn($response);

        $this->customerApi->expects($this->never())
            ->method('create');

        $consumer = $this->buildConsumerWithSelfHeal(true);

        $this->assertSame($response, $this->invokePerformApiRequest($consumer, $this->guestCustomer, $request));
    }

    /**
     * Self-heal ON + SKIPPED_NOT_TOMBSTONE (healthy record): normal update, no create.
     */
    public function testPerformApiRequestSelfHealSkippedNotTombstoneThenUpdates()
    {
        $request = ['email' => 'guest@example.com', 'externalid' => 'guest-5', 'connectionid' => 7];
        $activeCampaignId = 456;
        $response = ['ecomCustomer' => ['id' => $activeCampaignId]];

        $this->guestCustomer->expects($this->once())
            ->method('getActiveCampaignId')
            ->willReturn($activeCampaignId);

        $this->tombstoneRelinker->expects($this->once())
            ->method('relink')
            ->with($activeCampaignId, 'guest@example.com', 'guest-5', true)
            ->willReturn(TombstoneRelinker::RESULT_SKIPPED_NOT_TOMBSTONE);

        $this->client->expects($this->once())
            ->method('getCustomerApi')
            ->willReturn($this->customerApi);

        $this->customerApi->expects($this->once())
            ->method('update')
            ->with($activeCampaignId, ['ecomCustomer' => $request])
            ->willReturn($response);

        $this->customerApi->expects($this->never())
            ->method('create');

        $consumer = $this->buildConsumerWithSelfHeal(true);

        $this->assertSame($response, $this->invokePerformApiRequest($consumer, $this->guestCustomer, $request));
    }

    /**
     * Self-heal ON + NOT_FOUND (record truly gone): create() is called instead of update().
     */
    public function testPerformApiRequestSelfHealNotFoundCreates()
    {
        $request = ['email' => 'guest@example.com', 'externalid' => 'guest-5', 'connectionid' => 7];
        $deadId = 456;
        $newId = 999;
        $response = ['ecomCustomer' => ['id' => $newId]];

        $this->guestCustomer->expects($this->once())
            ->method('getActiveCampaignId')
            ->willReturn($deadId);

        $this->tombstoneRelinker->expects($this->once())
            ->method('relink')
            ->with($deadId, 'guest@example.com', 'guest-5', true)
            ->willReturn(TombstoneRelinker::RESULT_NOT_FOUND);

        $this->client->expects($this->once())
            ->method('getCustomerApi')
            ->willReturn($this->customerApi);

        $this->customerApi->expects($this->never())
            ->method('update');

        $this->customerApi->expects($this->once())
            ->method('create')
            ->with(['ecomCustomer' => $request])
            ->willReturn($response);

        $consumer = $this->buildConsumerWithSelfHeal(true);

        $this->assertSame($response, $this->invokePerformApiRequest($consumer, $this->guestCustomer, $request));
    }

    public function testConsumeCreate()
    {
        $customerData = ['email' => 'guest@example.com'];
        $request = ['email' => 'guest@example.com'];
        $activeCampaignId = 456;
        $response = ['ecomCustomer' => ['id' => $activeCampaignId]];

        $this->customerRepository->expects($this->once())
            ->method('getOrCreate')
            ->with($customerData)
            ->willReturn($this->guestCustomer);

        $this->customerRequestBuilder->expects($this->once())
            ->method('buildWithGuest')
            ->with($this->guestCustomer)
            ->willReturn($request);

        $this->guestCustomer->expects($this->atLeastOnce())
            ->method('getActiveCampaignId')
            ->willReturn(null);

        $this->client->expects($this->once())
            ->method('getCustomerApi')
            ->willReturn($this->customerApi);

        $this->customerApi->expects($this->once())
            ->method('create')
            ->with(['ecomCustomer' => $request])
            ->willReturn($response);

        $this->guestCustomer->expects($this->once())
            ->method('setActiveCampaignId')
            ->with($activeCampaignId)
            ->willReturnSelf();

        $this->failureRecorder->expects($this->once())
            ->method('recordSuccess')
            ->with($this->guestCustomer);

        $this->customerRepository->expects($this->once())
            ->method('save')
            ->with($this->guestCustomer);

        $this->exportGuestCustomerConsumer->consume(json_encode(['customer_data' => $customerData]));
    }

    public function testEmptyBodyDoesNotStrand()
    {
        $customerData = ['email' => 'guest@example.com'];
        $request = ['email' => 'guest@example.com'];

        $this->customerRepository->expects($this->once())
            ->method('getOrCreate')
            ->with($customerData)
            ->willReturn($this->guestCustomer);

        $this->customerRequestBuilder->expects($this->once())
            ->method('buildWithGuest')
            ->with($this->guestCustomer)
            ->willReturn($request);

        $this->guestCustomer->expects($this->atLeastOnce())
            ->method('getActiveCampaignId')
            ->willReturn(null);

        $this->client->expects($this->once())
            ->method('getCustomerApi')
            ->willReturn($this->customerApi);

        $this->customerApi->expects($this->once())
            ->method('create')
            ->with(['ecomCustomer' => $request])
            ->willReturn(['ecomCustomer' => []]);

        $this->guestCustomer->expects($this->never())
            ->method('setActiveCampaignId');

        $this->failureRecorder->expects($this->once())
            ->method('recordFailure')
            ->with($this->guestCustomer, 'empty_response', null);

        $this->customerRepository->expects($this->once())
            ->method('save')
            ->with($this->guestCustomer);

        $this->logger->expects($this->atLeastOnce())
            ->method('error');

        $this->exportGuestCustomerConsumer->consume(json_encode(['customer_data' => $customerData]));
    }

    public function testConsumeApiHttpException()
    {
        $customerData = ['email' => 'guest@example.com'];
        $request = ['email' => 'guest@example.com'];

        $this->customerRepository->expects($this->once())
            ->method('getOrCreate')
            ->with($customerData)
            ->willReturn($this->guestCustomer);

        $this->customerRequestBuilder->expects($this->once())
            ->method('buildWithGuest')
            ->with($this->guestCustomer)
            ->willReturn($request);

        $this->guestCustomer->expects($this->atLeastOnce())
            ->method('getActiveCampaignId')
            ->willReturn(null);

        $this->client->expects($this->once())
            ->method('getCustomerApi')
            ->willReturn($this->customerApi);

        /** @var MockObject|HttpException $httpException */
        $httpException = $this->createMock(HttpException::class);

        $this->customerApi->expects($this->once())
            ->method('create')
            ->with(['ecomCustomer' => $request])
            ->willThrowException($httpException);

        // Task 6.1: structured failure line; guests carry no magento id (magento_id=null).
        $this->logger->expects($this->once())
            ->method('error')
            ->with($this->logicalAnd(
                $this->stringContains('entity=guest_customer'),
                $this->stringContains('magento_id=null'),
                $this->stringContains('code=http_error')
            ));

        $this->guestCustomer->expects($this->never())
            ->method('setActiveCampaignId');

        $this->exportGuestCustomerConsumer->consume(json_encode(['customer_data' => $customerData]));
    }

    public function testConsumeDuplicateResolvesAndSaves()
    {
        $customerData = ['email' => 'guest@example.com'];
        $request = ['email' => 'guest@example.com', 'connectionid' => 7];
        $resolvedId = 777;
        $responseErrors = [['code' => 'duplicate']];

        $this->customerRepository->expects($this->once())
            ->method('getOrCreate')
            ->with($customerData)
            ->willReturn($this->guestCustomer);

        $this->customerRequestBuilder->expects($this->once())
            ->method('buildWithGuest')
            ->with($this->guestCustomer)
            ->willReturn($request);

        $this->guestCustomer->expects($this->atLeastOnce())
            ->method('getActiveCampaignId')
            ->willReturn(null);

        $this->client->expects($this->atLeastOnce())
            ->method('getCustomerApi')
            ->willReturn($this->customerApi);

        /** @var MockObject|UnprocessableEntityHttpException $unprocessableEntityHttpException */
        $unprocessableEntityHttpException = $this->createMock(UnprocessableEntityHttpException::class);

        $this->customerApi->expects($this->once())
            ->method('create')
            ->with(['ecomCustomer' => $request])
            ->willThrowException($unprocessableEntityHttpException);

        $unprocessableEntityHttpException->expects($this->atLeastOnce())
            ->method('getResponseErrors')
            ->willReturn($responseErrors);

        /** @var MockObject|PageInterface $page */
        $page = $this->createMock(PageInterface::class);
        $page->expects($this->atLeastOnce())
            ->method('getItems')
            ->willReturn([['id' => $resolvedId, 'email' => 'guest@example.com']]);

        $this->customerApi->expects($this->once())
            ->method('listPerPage')
            ->with(1, 0, ['filters' => ['email' => 'guest@example.com', 'connectionid' => 7]])
            ->willReturn($page);

        $this->guestCustomer->expects($this->once())
            ->method('setActiveCampaignId')
            ->with($resolvedId)
            ->willReturnSelf();

        $this->failureRecorder->expects($this->once())
            ->method('recordSuccess')
            ->with($this->guestCustomer);

        $this->customerRepository->expects($this->once())
            ->method('save')
            ->with($this->guestCustomer);

        $this->exportGuestCustomerConsumer->consume(json_encode(['customer_data' => $customerData]));
    }

    public function testConsumeSwallowsUnexpectedThrowableFromGetOrCreate()
    {
        $customerData = ['email' => 'guest@example.com', 'firstname' => null, 'lastname' => null];

        $this->customerRepository->expects($this->once())
            ->method('getOrCreate')
            ->with($customerData)
            ->willThrowException(new \TypeError('firstname must be of type string, null given'));

        // entity is unavailable when getOrCreate throws → null local id, no save
        $this->customerRequestBuilder->expects($this->never())
            ->method('buildWithGuest');

        $this->failureRecorder->expects($this->never())
            ->method('recordFailure');

        $this->customerRepository->expects($this->never())
            ->method('save');

        $this->logger->expects($this->atLeastOnce())
            ->method('error')
            ->with($this->logicalAnd(
                $this->stringContains('entity=guest_customer'),
                $this->stringContains('local_id=null'),
                $this->stringContains('code=unexpected_error')
            ));

        // Must NOT propagate.
        $this->exportGuestCustomerConsumer->consume(json_encode(['customer_data' => $customerData]));
    }

    public function testConsumeSwallowsUnexpectedThrowableWithEntityAvailable()
    {
        $customerData = ['email' => 'guest@example.com'];

        $this->customerRepository->expects($this->once())
            ->method('getOrCreate')
            ->with($customerData)
            ->willReturn($this->guestCustomer);

        $this->guestCustomer->expects($this->atLeastOnce())
            ->method('getId')
            ->willReturn(99);

        // A later step throws after the entity is available.
        $this->customerRequestBuilder->expects($this->once())
            ->method('buildWithGuest')
            ->with($this->guestCustomer)
            ->willThrowException(new \RuntimeException('boom'));

        $this->failureRecorder->expects($this->once())
            ->method('recordFailure')
            ->with($this->guestCustomer, 'unexpected_error', 'boom');

        $this->customerRepository->expects($this->once())
            ->method('save')
            ->with($this->guestCustomer);

        $this->logger->expects($this->atLeastOnce())
            ->method('error')
            ->with($this->logicalAnd(
                $this->stringContains('entity=guest_customer'),
                $this->stringContains('local_id=99'),
                $this->stringContains('code=unexpected_error')
            ));

        // Must NOT propagate.
        $this->exportGuestCustomerConsumer->consume(json_encode(['customer_data' => $customerData]));
    }

    public function testConsumeDuplicateLookupEmptyDoesNotFatal()
    {
        $customerData = ['email' => 'guest@example.com'];
        $request = ['email' => 'guest@example.com', 'connectionid' => 7];
        $responseErrors = [['code' => 'duplicate']];

        $this->customerRepository->expects($this->once())
            ->method('getOrCreate')
            ->with($customerData)
            ->willReturn($this->guestCustomer);

        $this->customerRequestBuilder->expects($this->once())
            ->method('buildWithGuest')
            ->with($this->guestCustomer)
            ->willReturn($request);

        $this->guestCustomer->expects($this->atLeastOnce())
            ->method('getActiveCampaignId')
            ->willReturn(null);

        $this->client->expects($this->atLeastOnce())
            ->method('getCustomerApi')
            ->willReturn($this->customerApi);

        /** @var MockObject|UnprocessableEntityHttpException $unprocessableEntityHttpException */
        $unprocessableEntityHttpException = $this->createMock(UnprocessableEntityHttpException::class);

        $this->customerApi->expects($this->once())
            ->method('create')
            ->with(['ecomCustomer' => $request])
            ->willThrowException($unprocessableEntityHttpException);

        $unprocessableEntityHttpException->expects($this->atLeastOnce())
            ->method('getResponseErrors')
            ->willReturn($responseErrors);

        /** @var MockObject|PageInterface $page */
        $page = $this->createMock(PageInterface::class);
        $page->expects($this->atLeastOnce())
            ->method('getItems')
            ->willReturn([]);

        $this->customerApi->expects($this->once())
            ->method('listPerPage')
            ->with(1, 0, ['filters' => ['email' => 'guest@example.com', 'connectionid' => 7]])
            ->willReturn($page);

        $this->guestCustomer->expects($this->never())
            ->method('setActiveCampaignId');

        $this->customerRepository->expects($this->never())
            ->method('save');

        $this->logger->expects($this->atLeastOnce())
            ->method('error');

        $this->exportGuestCustomerConsumer->consume(json_encode(['customer_data' => $customerData]));
    }

    public function testConsumeDuplicateEmailMismatchDoesNotSave()
    {
        $customerData = ['email' => 'guest@example.com'];
        $request = ['email' => 'guest@example.com', 'connectionid' => 7];
        $responseErrors = [['code' => 'duplicate']];

        $this->customerRepository->expects($this->once())
            ->method('getOrCreate')
            ->with($customerData)
            ->willReturn($this->guestCustomer);

        $this->customerRequestBuilder->expects($this->once())
            ->method('buildWithGuest')
            ->with($this->guestCustomer)
            ->willReturn($request);

        $this->guestCustomer->expects($this->atLeastOnce())
            ->method('getActiveCampaignId')
            ->willReturn(null);

        $this->client->expects($this->atLeastOnce())
            ->method('getCustomerApi')
            ->willReturn($this->customerApi);

        /** @var MockObject|UnprocessableEntityHttpException $unprocessableEntityHttpException */
        $unprocessableEntityHttpException = $this->createMock(UnprocessableEntityHttpException::class);

        $this->customerApi->expects($this->once())
            ->method('create')
            ->with(['ecomCustomer' => $request])
            ->willThrowException($unprocessableEntityHttpException);

        $unprocessableEntityHttpException->expects($this->atLeastOnce())
            ->method('getResponseErrors')
            ->willReturn($responseErrors);

        /** @var MockObject|PageInterface $page */
        $page = $this->createMock(PageInterface::class);
        $page->expects($this->atLeastOnce())
            ->method('getItems')
            ->willReturn([['id' => 777, 'email' => 'someone-else@example.com']]);

        $this->customerApi->expects($this->once())
            ->method('listPerPage')
            ->with(1, 0, ['filters' => ['email' => 'guest@example.com', 'connectionid' => 7]])
            ->willReturn($page);

        $this->guestCustomer->expects($this->never())
            ->method('setActiveCampaignId');

        $this->customerRepository->expects($this->never())
            ->method('save');

        $this->logger->expects($this->atLeastOnce())
            ->method('error');

        $this->exportGuestCustomerConsumer->consume(json_encode(['customer_data' => $customerData]));
    }
}
