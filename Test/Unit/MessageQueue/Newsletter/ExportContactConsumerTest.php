<?php
declare(strict_types=1);
/**
 */

namespace CommerceLeague\ActiveCampaign\Test\Unit\MessageQueue\Newsletter;

use CommerceLeague\ActiveCampaign\Api\ContactRepositoryInterface;
use CommerceLeague\ActiveCampaign\Api\Data\ContactInterface;
use CommerceLeague\ActiveCampaign\Gateway\Client;
use CommerceLeague\ActiveCampaign\Gateway\Request\ContactBuilder as ContactRequestBuilder;
use CommerceLeague\ActiveCampaign\Logger\Logger;
use CommerceLeague\ActiveCampaign\MessageQueue\Newsletter\ExportContactConsumer;
use CommerceLeague\ActiveCampaignApi\Api\ContactApiResourceInterface;
use CommerceLeague\ActiveCampaignApi\Exception\HttpException;
use CommerceLeague\ActiveCampaignApi\Exception\UnprocessableEntityHttpException;
use Magento\Framework\Phrase;
use Magento\Newsletter\Model\Subscriber;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Magento\Newsletter\Model\SubscriberFactory;

class ExportContactConsumerTest extends TestCase
{
    /**
     * @var MockObject|SubscriberFactory
     */
    protected $subscriberFactory;

    /**
     * @var MockObject|Subscriber
     */
    protected $subscriber;

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
     * @var ExportContactConsumer
     */
    protected $exportContactConsumer;

    protected function setUp()
    {
        $this->subscriberFactory = $this->getMockBuilder(SubscriberFactory::class)
            ->disableOriginalConstructor()
            ->setMethods(['create'])
            ->getMock();

        $this->subscriber = $this->createMock(Subscriber::class);

        $this->subscriberFactory->expects($this->any())
            ->method('create')
            ->willReturn($this->subscriber);

        $this->logger = $this->createMock(Logger::class);
        $this->contactRepository = $this->createMock(ContactRepositoryInterface::class);
        $this->contactRequestBuilder = $this->createMock(ContactRequestBuilder::class);
        $this->client = $this->createMock(Client::class);
        $this->contact = $this->createMock(ContactInterface::class);
        $this->contactApi = $this->createMock(ContactApiResourceInterface::class);

        $this->exportContactConsumer = new ExportContactConsumer(
            $this->subscriberFactory,
            $this->logger,
            $this->contactRepository,
            $this->contactRequestBuilder,
            $this->client
        );
    }

    public function testConsumeWithAbsentSubscriber()
    {
        $email = 'example@example.com';

        $this->subscriber->expects($this->once())
            ->method('loadByEmail')
            ->with($email)
            ->willReturnSelf();

        $this->subscriber->expects($this->once())
            ->method('getId')
            ->willReturn(null);

        $this->logger->expects($this->once())
            ->method('error')
            ->with(new Phrase('The Subscriber with the "%1" email doesn\'t exist', [$email]));

        $this->contactRepository->expects($this->never())
            ->method('getOrCreateByEmail');

        $this->exportContactConsumer->consume(json_encode(['email' => $email]));
    }

    public function testConsumeApiHttpException()
    {
        $email = 'example@example.com';
        $request = ['request'];

        $this->subscriber->expects($this->once())
            ->method('loadByEmail')
            ->with($email)
            ->willReturnSelf();

        $this->subscriber->expects($this->once())
            ->method('getId')
            ->willReturn(123);

        $this->subscriber->expects($this->once())
            ->method('getEmail')
            ->willReturn($email);

        $this->contactRepository->expects($this->once())
            ->method('getOrCreateByEmail')
            ->with($email)
            ->willReturn($this->contact);

        $this->contactRequestBuilder->expects($this->once())
            ->method('buildWithSubscriber')
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

        $this->exportContactConsumer->consume(json_encode(['email' => $email]));
    }

    public function testConsumeApiUnprocessableEntityHttpExceptionException()
    {
        $email = 'example@example.com';
        $request = ['request'];
        $responseErrors = ['first error', 'second error'];

        $this->subscriber->expects($this->once())
            ->method('loadByEmail')
            ->with($email)
            ->willReturnSelf();

        $this->subscriber->expects($this->once())
            ->method('getId')
            ->willReturn(123);

        $this->subscriber->expects($this->once())
            ->method('getEmail')
            ->willReturn($email);

        $this->contactRepository->expects($this->once())
            ->method('getOrCreateByEmail')
            ->with($email)
            ->willReturn($this->contact);

        $this->contactRequestBuilder->expects($this->once())
            ->method('buildWithSubscriber')
            ->willReturn($request);

        $this->client->expects($this->once())
            ->method('getContactApi')
            ->willReturn($this->contactApi);

        /** @var MockObject|UnprocessableEntityHttpException $unprocessableEntityHttpException */
        $unprocessableEntityHttpException = $this->createMock(UnprocessableEntityHttpException::class);

        $this->contactApi->expects($this->once())
            ->method('upsert')
            ->with(['contact' => $request])
            ->willThrowException($unprocessableEntityHttpException);

        $this->logger->expects($this->exactly(2))
            ->method('error');

        $unprocessableEntityHttpException->expects($this->once())
            ->method('getResponseErrors')
            ->willReturn($responseErrors);

        $this->logger->expects($this->at(1))
            ->method('error')
            ->with(print_r($responseErrors, true));

        $this->contact->expects($this->never())
            ->method('setActiveCampaignId');

        $this->exportContactConsumer->consume(json_encode(['email' => $email]));
    }

    public function testConsume()
    {
        $email = 'example@example.com';
        $request = ['request'];
        $activeCampaignId = 456;
        $response = ['contact' => ['id' => $activeCampaignId]];

        $this->subscriber->expects($this->once())
            ->method('loadByEmail')
            ->with($email)
            ->willReturnSelf();

        $this->subscriber->expects($this->once())
            ->method('getId')
            ->willReturn(123);

        $this->subscriber->expects($this->once())
            ->method('getEmail')
            ->willReturn($email);

        $this->contactRepository->expects($this->once())
            ->method('getOrCreateByEmail')
            ->with($email)
            ->willReturn($this->contact);

        $this->contactRequestBuilder->expects($this->once())
            ->method('buildWithSubscriber')
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

        $this->exportContactConsumer->consume(json_encode(['email' => $email]));
    }
}
