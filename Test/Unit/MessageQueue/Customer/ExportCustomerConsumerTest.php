<?php
declare(strict_types=1);
/**
 */

namespace CommerceLeague\ActiveCampaign\Test\Unit\MessageQueue\Customer;

use CommerceLeague\ActiveCampaign\Api\CustomerRepositoryInterface;
use CommerceLeague\ActiveCampaign\Api\Data\CustomerInterface;
use CommerceLeague\ActiveCampaign\Gateway\Client;
use CommerceLeague\ActiveCampaign\Gateway\Request\CustomerBuilder as CustomerRequestBuilder;
use CommerceLeague\ActiveCampaign\Helper\Config;
use CommerceLeague\ActiveCampaign\Logger\Logger;
use CommerceLeague\ActiveCampaign\MessageQueue\Customer\ExportCustomerConsumer;
use CommerceLeague\ActiveCampaign\Model\Export\BackoffState;
use CommerceLeague\ActiveCampaign\Model\Export\FailureRecorder;
use CommerceLeague\ActiveCampaign\Model\Tombstone\TombstoneRelinker;
use CommerceLeague\ActiveCampaign\Test\Unit\AbstractTestCase;
use CommerceLeague\ActiveCampaignApi\Api\CustomerApiResourceInterface;
use CommerceLeague\ActiveCampaignApi\Exception\HttpException;
use Magento\Customer\Api\CustomerRepositoryInterface as MagentoCustomerRepositoryInterface;
use Magento\Customer\Api\Data\CustomerInterface as MagentoCustomerInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Phrase;
use PHPUnit\Framework\MockObject\MockObject;

class ExportCustomerConsumerTest extends AbstractTestCase
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
     * @var ExportCustomerConsumer
     */
    protected $exportCustomerConsumer;

    protected function setUp(): void
    {
        $this->magentoCustomerRepository = $this->createMock(MagentoCustomerRepositoryInterface::class);
        $this->logger = $this->createMock(Logger::class);
        $this->customerRepository = $this->createMock(CustomerRepositoryInterface::class);
        $this->customerRequestBuilder = $this->createMock(CustomerRequestBuilder::class);
        $this->client = $this->createMock(Client::class);
        $this->customer = $this->createMock(CustomerInterface::class);
        $this->customerApi = $this->createMock(CustomerApiResourceInterface::class);
        $this->magentoCustomer = $this->createMock(MagentoCustomerInterface::class);
        $this->failureRecorder = $this->createMock(FailureRecorder::class);
        $this->backoffState = $this->createMock(BackoffState::class);
        $this->config = $this->createMock(Config::class);
        $this->tombstoneRelinker = $this->createMock(TombstoneRelinker::class);

        // Default OFF: zero behaviour change for existing tests.
        $this->config->method('isTombstoneSelfHealEnabled')->willReturn(false);

        $this->exportCustomerConsumer = new ExportCustomerConsumer(
            $this->magentoCustomerRepository,
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
            ->with($exception);

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

        // Task 6.1: structured failure line with entity type, magento id and AC code.
        $this->logger->expects($this->once())
            ->method('error')
            ->with($this->logicalAnd(
                $this->stringContains('entity=customer'),
                $this->stringContains('magento_id=' . $magentoCustomerId),
                $this->stringContains('code=http_error')
            ));

        $this->customer->expects($this->never())
            ->method('setActiveCampaignId');

        $this->exportCustomerConsumer->consume(json_encode(['magento_customer_id' => $magentoCustomerId]));
    }

    public function testConsumeApiUnprocessableEntityHttpExceptionException()
    {
        $magentoCustomerId = 123;
        $request = ['request'];
        $responseErrors = ['first error', 'second error'];
        $apiResponseKey = 'ecomCustomer';
        $apiMethod = 'create';

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

        $this->unprocessableEntityHttpException($this->customerApi, $this->logger, $request, $responseErrors, $apiResponseKey, $apiMethod);

        $this->customer->expects($this->never())
            ->method('setActiveCampaignId');

        $this->exportCustomerConsumer->consume(json_encode(['magento_customer_id' => $magentoCustomerId]));
    }

    public function testEmptyBodyDoesNotStrand()
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
            ->willReturn(['ecomCustomer' => []]);

        $this->customer->expects($this->never())
            ->method('setActiveCampaignId');

        $this->failureRecorder->expects($this->once())
            ->method('recordFailure')
            ->with($this->customer, 'empty_response', null);

        $this->customerRepository->expects($this->once())
            ->method('save')
            ->with($this->customer);

        $this->logger->expects($this->atLeastOnce())
            ->method('error');

        $this->exportCustomerConsumer->consume(json_encode(['magento_customer_id' => $magentoCustomerId]));
    }

    public function testConsumeDuplicateLookupEmptyDoesNotFatal()
    {
        $magentoCustomerId = 123;
        $request = ['email' => 'example@example.com', 'connectionid' => 7];
        $responseErrors = [['code' => 'duplicate']];

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

        $this->client->expects($this->atLeastOnce())
            ->method('getCustomerApi')
            ->willReturn($this->customerApi);

        /** @var MockObject|\CommerceLeague\ActiveCampaignApi\Exception\UnprocessableEntityHttpException $unprocessableEntityHttpException */
        $unprocessableEntityHttpException =
            $this->createMock(\CommerceLeague\ActiveCampaignApi\Exception\UnprocessableEntityHttpException::class);

        $this->customerApi->expects($this->once())
            ->method('create')
            ->with(['ecomCustomer' => $request])
            ->willThrowException($unprocessableEntityHttpException);

        $unprocessableEntityHttpException->expects($this->atLeastOnce())
            ->method('getResponseErrors')
            ->willReturn($responseErrors);

        /** @var MockObject|\CommerceLeague\ActiveCampaignApi\Paginator\PageInterface $page */
        $page = $this->createMock(\CommerceLeague\ActiveCampaignApi\Paginator\PageInterface::class);
        $page->expects($this->atLeastOnce())
            ->method('getItems')
            ->willReturn([]);

        $this->customerApi->expects($this->once())
            ->method('listPerPage')
            ->willReturn($page);

        $this->customer->expects($this->never())
            ->method('setActiveCampaignId');

        $this->customerRepository->expects($this->never())
            ->method('save');

        $this->exportCustomerConsumer->consume(json_encode(['magento_customer_id' => $magentoCustomerId]));
    }

    public function testConsumeDuplicateResolvesAndSaves()
    {
        $magentoCustomerId = 123;
        $request = ['email' => 'example@example.com', 'connectionid' => 7];
        $responseErrors = [['code' => 'duplicate']];
        $activeCampaignId = 789;

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

        $this->client->expects($this->atLeastOnce())
            ->method('getCustomerApi')
            ->willReturn($this->customerApi);

        /** @var MockObject|\CommerceLeague\ActiveCampaignApi\Exception\UnprocessableEntityHttpException $unprocessableEntityHttpException */
        $unprocessableEntityHttpException =
            $this->createMock(\CommerceLeague\ActiveCampaignApi\Exception\UnprocessableEntityHttpException::class);

        $this->customerApi->expects($this->once())
            ->method('create')
            ->with(['ecomCustomer' => $request])
            ->willThrowException($unprocessableEntityHttpException);

        $unprocessableEntityHttpException->expects($this->atLeastOnce())
            ->method('getResponseErrors')
            ->willReturn($responseErrors);

        /** @var MockObject|\CommerceLeague\ActiveCampaignApi\Paginator\PageInterface $page */
        $page = $this->createMock(\CommerceLeague\ActiveCampaignApi\Paginator\PageInterface::class);
        $page->expects($this->atLeastOnce())
            ->method('getItems')
            ->willReturn([['id' => $activeCampaignId, 'email' => 'example@example.com']]);

        $this->customerApi->expects($this->once())
            ->method('listPerPage')
            ->with(1, 0, ['filters' => ['email' => $request['email'], 'connectionid' => $request['connectionid']]])
            ->willReturn($page);

        $this->customer->expects($this->once())
            ->method('setActiveCampaignId')
            ->with($activeCampaignId)
            ->willReturnSelf();

        $this->failureRecorder->expects($this->once())
            ->method('recordSuccess')
            ->with($this->customer);

        $this->customerRepository->expects($this->once())
            ->method('save')
            ->with($this->customer);

        $this->exportCustomerConsumer->consume(json_encode(['magento_customer_id' => $magentoCustomerId]));
    }

    public function testConsumeDuplicateEmailMismatchDoesNotSave()
    {
        $magentoCustomerId = 123;
        $request = ['email' => 'example@example.com', 'connectionid' => 7];
        $responseErrors = [['code' => 'duplicate']];

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

        $this->client->expects($this->atLeastOnce())
            ->method('getCustomerApi')
            ->willReturn($this->customerApi);

        /** @var MockObject|\CommerceLeague\ActiveCampaignApi\Exception\UnprocessableEntityHttpException $unprocessableEntityHttpException */
        $unprocessableEntityHttpException =
            $this->createMock(\CommerceLeague\ActiveCampaignApi\Exception\UnprocessableEntityHttpException::class);

        $this->customerApi->expects($this->once())
            ->method('create')
            ->with(['ecomCustomer' => $request])
            ->willThrowException($unprocessableEntityHttpException);

        $unprocessableEntityHttpException->expects($this->atLeastOnce())
            ->method('getResponseErrors')
            ->willReturn($responseErrors);

        /** @var MockObject|\CommerceLeague\ActiveCampaignApi\Paginator\PageInterface $page */
        $page = $this->createMock(\CommerceLeague\ActiveCampaignApi\Paginator\PageInterface::class);
        $page->expects($this->atLeastOnce())
            ->method('getItems')
            ->willReturn([['id' => 789, 'email' => 'someone-else@example.com']]);

        $this->customerApi->expects($this->once())
            ->method('listPerPage')
            ->with(1, 0, ['filters' => ['email' => $request['email'], 'connectionid' => $request['connectionid']]])
            ->willReturn($page);

        $this->customer->expects($this->never())
            ->method('setActiveCampaignId');

        $this->customerRepository->expects($this->never())
            ->method('save');

        $this->exportCustomerConsumer->consume(json_encode(['magento_customer_id' => $magentoCustomerId]));
    }

    public function testConsumeSwallowsUnexpectedThrowableAndRecordsFailure()
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

        $this->customerRepository->expects($this->once())
            ->method('getOrCreateByMagentoCustomerId')
            ->willReturn($this->customer);

        $this->customerRequestBuilder->expects($this->once())
            ->method('build')
            ->with($this->magentoCustomer)
            ->willReturn($request);

        $this->customer->expects($this->atLeastOnce())
            ->method('getId')
            ->willReturn(88);

        // An unexpected, non-Http throwable from a body dependency.
        $this->client->expects($this->once())
            ->method('getCustomerApi')
            ->willThrowException(new \RuntimeException('boom'));

        $this->failureRecorder->expects($this->once())
            ->method('recordFailure')
            ->with($this->customer, 'unexpected_error', 'boom');

        $this->customerRepository->expects($this->once())
            ->method('save')
            ->with($this->customer);

        $this->logger->expects($this->atLeastOnce())
            ->method('error')
            ->with($this->logicalAnd(
                $this->stringContains('entity=customer'),
                $this->stringContains('local_id=88'),
                $this->stringContains('magento_id=' . $magentoCustomerId),
                $this->stringContains('code=unexpected_error')
            ));

        // Must NOT propagate.
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

        // Self-heal default OFF -> relinker must never be touched.
        $this->tombstoneRelinker->expects($this->never())
            ->method('relink');

        $this->customer->expects($this->once())
            ->method('setActiveCampaignId')
            ->with($activeCampaignId)
            ->willReturnSelf();

        $this->failureRecorder->expects($this->once())
            ->method('recordSuccess')
            ->with($this->customer);

        $this->customerRepository->expects($this->once())
            ->method('save')
            ->with($this->customer);

        $this->exportCustomerConsumer->consume(json_encode(['magento_customer_id' => $magentoCustomerId]));
    }

    /**
     * Self-heal ON + RELINKED: the relinker runs once (id/email/externalid),
     * then the normal update still happens.
     */
    public function testConsumeUpdateSelfHealRelinkedThenUpdates()
    {
        $magentoCustomerId = 123;
        $request = ['email' => 'real@example.com', 'externalid' => '123', 'connectionid' => 7];
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

        $this->config = $this->createMock(Config::class);
        $this->config->method('isTombstoneSelfHealEnabled')->willReturn(true);

        $this->tombstoneRelinker->expects($this->once())
            ->method('relink')
            ->with($activeCampaignId, 'real@example.com', '123', true)
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

        $this->customer->expects($this->once())
            ->method('setActiveCampaignId')
            ->with($activeCampaignId)
            ->willReturnSelf();

        $this->failureRecorder->expects($this->once())
            ->method('recordSuccess')
            ->with($this->customer);

        $this->customerRepository->expects($this->once())
            ->method('save')
            ->with($this->customer);

        $consumer = new ExportCustomerConsumer(
            $this->magentoCustomerRepository,
            $this->logger,
            $this->customerRepository,
            $this->customerRequestBuilder,
            $this->client,
            $this->failureRecorder,
            $this->backoffState,
            $this->config,
            $this->tombstoneRelinker
        );

        $consumer->consume(json_encode(['magento_customer_id' => $magentoCustomerId]));
    }

    /**
     * Self-heal ON + SKIPPED_NOT_TOMBSTONE (healthy record): normal update happens,
     * idempotent (no create).
     */
    public function testConsumeUpdateSelfHealSkippedNotTombstoneThenUpdates()
    {
        $magentoCustomerId = 123;
        $request = ['email' => 'real@example.com', 'externalid' => '123', 'connectionid' => 7];
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

        $this->config = $this->createMock(Config::class);
        $this->config->method('isTombstoneSelfHealEnabled')->willReturn(true);

        $this->tombstoneRelinker->expects($this->once())
            ->method('relink')
            ->with($activeCampaignId, 'real@example.com', '123', true)
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

        $this->customer->expects($this->once())
            ->method('setActiveCampaignId')
            ->with($activeCampaignId)
            ->willReturnSelf();

        $this->customerRepository->expects($this->once())
            ->method('save')
            ->with($this->customer);

        $consumer = new ExportCustomerConsumer(
            $this->magentoCustomerRepository,
            $this->logger,
            $this->customerRepository,
            $this->customerRequestBuilder,
            $this->client,
            $this->failureRecorder,
            $this->backoffState,
            $this->config,
            $this->tombstoneRelinker
        );

        $consumer->consume(json_encode(['magento_customer_id' => $magentoCustomerId]));
    }

    /**
     * Self-heal ON + NOT_FOUND (record truly gone): create() is called instead of
     * update(), and the consumer persists the new id from the create response.
     */
    public function testConsumeUpdateSelfHealNotFoundCreatesAndPersistsNewId()
    {
        $magentoCustomerId = 123;
        $request = ['email' => 'real@example.com', 'externalid' => '123', 'connectionid' => 7];
        $deadId = 456;
        $newId = 999;
        $response = ['ecomCustomer' => ['id' => $newId]];

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
            ->willReturn($deadId);

        $this->config = $this->createMock(Config::class);
        $this->config->method('isTombstoneSelfHealEnabled')->willReturn(true);

        $this->tombstoneRelinker->expects($this->once())
            ->method('relink')
            ->with($deadId, 'real@example.com', '123', true)
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

        $this->customer->expects($this->once())
            ->method('setActiveCampaignId')
            ->with($newId)
            ->willReturnSelf();

        $this->failureRecorder->expects($this->once())
            ->method('recordSuccess')
            ->with($this->customer);

        $this->customerRepository->expects($this->once())
            ->method('save')
            ->with($this->customer);

        $consumer = new ExportCustomerConsumer(
            $this->magentoCustomerRepository,
            $this->logger,
            $this->customerRepository,
            $this->customerRequestBuilder,
            $this->client,
            $this->failureRecorder,
            $this->backoffState,
            $this->config,
            $this->tombstoneRelinker
        );

        $consumer->consume(json_encode(['magento_customer_id' => $magentoCustomerId]));
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

        $this->failureRecorder->expects($this->once())
            ->method('recordSuccess')
            ->with($this->customer);

        $this->customerRepository->expects($this->once())
            ->method('save')
            ->with($this->customer);

        $this->exportCustomerConsumer->consume(json_encode(['magento_customer_id' => $magentoCustomerId]));
    }
}
