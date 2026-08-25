<?php
declare(strict_types=1);
/**
 * Copyright © André Flitsch. All rights reserved.
 * See license.md for license details.
 */

namespace CommerceLeague\ActiveCampaign\Test\Unit\Model\Tombstone;

use CommerceLeague\ActiveCampaign\Gateway\Client;
use CommerceLeague\ActiveCampaign\Model\ActiveCampaign\Contact;
use CommerceLeague\ActiveCampaign\Model\ResourceModel\ActiveCampaign\Contact\Collection as ContactCollection;
use CommerceLeague\ActiveCampaign\Model\ResourceModel\ActiveCampaign\Contact\CollectionFactory as ContactCollectionFactory;
use CommerceLeague\ActiveCampaign\Model\Tombstone\ContactTombstoneChecker;
use CommerceLeague\ActiveCampaign\Test\Unit\AbstractTestCase;
use CommerceLeague\ActiveCampaignApi\Api\ContactApiResourceInterface;
use CommerceLeague\ActiveCampaignApi\Exception\HttpException;
use CommerceLeague\ActiveCampaignApi\Exception\NotFoundHttpException;
use PHPUnit\Framework\MockObject\MockObject;

class ContactTombstoneCheckerTest extends AbstractTestCase
{
    /**
     * @var MockObject|ContactCollectionFactory
     */
    private $contactCollectionFactory;

    /**
     * @var MockObject|ContactCollection
     */
    private $contactCollection;

    /**
     * @var MockObject|Client
     */
    private $client;

    /**
     * @var MockObject|ContactApiResourceInterface
     */
    private $contactApi;

    /**
     * @var ContactTombstoneChecker
     */
    private $checker;

    protected function setUp(): void
    {
        $this->contactCollectionFactory = $this->getMockBuilder(ContactCollectionFactory::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['create'])
            ->getMock();

        $this->contactCollection = $this->createMock(ContactCollection::class);

        $this->contactCollectionFactory->method('create')->willReturn($this->contactCollection);

        $this->client     = $this->createMock(Client::class);
        $this->contactApi = $this->createMock(ContactApiResourceInterface::class);
        $this->client->method('getContactApi')->willReturn($this->contactApi);

        $this->checker = $this->getMockBuilder(ContactTombstoneChecker::class)
            ->setConstructorArgs([$this->contactCollectionFactory, $this->client])
            ->onlyMethods(['sleep'])
            ->getMock();
    }

    private function contact(int $id, string $email, int $activeCampaignId): Contact
    {
        $contact = $this->getMockBuilder(Contact::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getId', 'getEmail', 'getActiveCampaignId'])
            ->getMock();

        $contact->method('getId')->willReturn($id);
        $contact->method('getEmail')->willReturn($email);
        $contact->method('getActiveCampaignId')->willReturn($activeCampaignId);

        return $contact;
    }

    public function testFiltersToNonNullActiveCampaignId(): void
    {
        $this->contactCollection->expects($this->once())
            ->method('addFieldToFilter')
            ->with('activecampaign_id', ['notnull' => true]);

        $this->contactCollection->method('getItems')->willReturn([]);

        $this->checker->check(null, 0);
    }

    public function testEmailFilterIsAppliedWhenProvided(): void
    {
        $calls = [];
        $this->contactCollection->method('addFieldToFilter')
            ->willReturnCallback(function (string $field, $condition) use (&$calls) {
                $calls[$field] = $condition;
                return $this->contactCollection;
            });

        $this->contactCollection->method('getItems')->willReturn([]);

        $this->checker->check('jane.doe@example.com', 0);

        $this->assertSame(['notnull' => true], $calls['activecampaign_id']);
        $this->assertSame('jane.doe@example.com', $calls['email']);
    }

    public function testLimitSetsPageSize(): void
    {
        $select = $this->createMock(\Magento\Framework\DB\Select::class);
        $select->expects($this->once())->method('orderRand');

        $this->contactCollection->method('addFieldToFilter')->willReturnSelf();
        $this->contactCollection->method('getSelect')->willReturn($select);
        $this->contactCollection->expects($this->once())->method('setPageSize')->with(50);
        $this->contactCollection->method('getItems')->willReturn([]);

        $this->checker->check(null, 50);
    }

    public function testFoundWhenGetSucceeds(): void
    {
        $this->contactCollection->method('addFieldToFilter')->willReturnSelf();
        $this->contactCollection->method('getItems')->willReturn([
            $this->contact(21328, 'jane.doe@example.com', 105872),
        ]);

        $this->contactApi->expects($this->once())
            ->method('get')
            ->with(105872)
            ->willReturn(['contact' => ['id' => 105872]]);

        $tally = $this->checker->check(null, 0);

        $this->assertSame(1, $tally[ContactTombstoneChecker::RESULT_FOUND]);
        $this->assertSame(0, $tally[ContactTombstoneChecker::RESULT_NOT_FOUND]);
    }

    public function testNotFoundWhenGetThrows404(): void
    {
        $this->contactCollection->method('addFieldToFilter')->willReturnSelf();
        $this->contactCollection->method('getItems')->willReturn([
            $this->contact(21602, 'john.doe@example.com', 106578),
        ]);

        $this->contactApi->expects($this->once())
            ->method('get')
            ->with(106578)
            ->willThrowException($this->createMock(NotFoundHttpException::class));

        $record = null;
        $tally  = $this->checker->check(null, 0, function (array $r) use (&$record): void {
            $record = $r;
        });

        $this->assertSame(1, $tally[ContactTombstoneChecker::RESULT_NOT_FOUND]);
        $this->assertSame([
            'entityId'         => 21602,
            'email'            => 'john.doe@example.com',
            'activeCampaignId' => 106578,
            'outcome'          => ContactTombstoneChecker::RESULT_NOT_FOUND,
        ], $record);
    }

    public function testErrorTallyOnUnexpectedException(): void
    {
        $this->contactCollection->method('addFieldToFilter')->willReturnSelf();
        $this->contactCollection->method('getItems')->willReturn([
            $this->contact(1, 'x@example.com', 999),
        ]);

        $this->contactApi->method('get')->willThrowException(new \RuntimeException('503 backoff'));

        $tally = $this->checker->check(null, 0);

        $this->assertSame(1, $tally[ContactTombstoneChecker::RESULT_ERROR]);
    }

    /**
     * Builds a real HttpException carrying the given HTTP status code, so
     * $e->getCode() returns it (getCode() is final and cannot be mocked).
     */
    private function httpExceptionWithCode(int $statusCode): HttpException
    {
        $request  = $this->createMock(\Psr\Http\Message\RequestInterface::class);
        $response = $this->createMock(\Psr\Http\Message\ResponseInterface::class);
        $response->method('getStatusCode')->willReturn($statusCode);

        return new HttpException('boom', $request, $response);
    }

    public function testTransientErrorRetriesThenSucceeds(): void
    {
        $this->contactCollection->method('addFieldToFilter')->willReturnSelf();
        $this->contactCollection->method('getItems')->willReturn([
            $this->contact(1, 'x@example.com', 999),
        ]);

        $this->contactApi->expects($this->exactly(3))
            ->method('get')
            ->willReturnOnConsecutiveCalls(
                $this->throwException($this->httpExceptionWithCode(429)),
                $this->throwException($this->httpExceptionWithCode(503)),
                ['contact' => ['id' => 999]]
            );

        $tally = $this->checker->check(null, 0);

        $this->assertSame(1, $tally[ContactTombstoneChecker::RESULT_FOUND]);
        $this->assertSame(0, $tally[ContactTombstoneChecker::RESULT_ERROR]);
    }

    public function testTransientErrorGivesUpAfterMaxRetries(): void
    {
        $this->contactCollection->method('addFieldToFilter')->willReturnSelf();
        $this->contactCollection->method('getItems')->willReturn([
            $this->contact(1, 'x@example.com', 999),
        ]);

        // MAX_TRANSIENT_RETRIES = 3 retries on top of the first attempt = 4 calls total.
        $this->contactApi->expects($this->exactly(4))
            ->method('get')
            ->willThrowException($this->httpExceptionWithCode(429));

        $tally = $this->checker->check(null, 0);

        $this->assertSame(1, $tally[ContactTombstoneChecker::RESULT_ERROR]);
    }

    public function testNonTransientHttpErrorFailsWithoutRetry(): void
    {
        $this->contactCollection->method('addFieldToFilter')->willReturnSelf();
        $this->contactCollection->method('getItems')->willReturn([
            $this->contact(1, 'x@example.com', 999),
        ]);

        $this->contactApi->expects($this->once())
            ->method('get')
            ->willThrowException($this->httpExceptionWithCode(401));

        $tally = $this->checker->check(null, 0);

        $this->assertSame(1, $tally[ContactTombstoneChecker::RESULT_ERROR]);
    }
}
