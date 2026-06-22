<?php
declare(strict_types=1);
/**
 * Copyright © André Flitsch. All rights reserved.
 * See license.md for license details.
 */

namespace CommerceLeague\ActiveCampaign\Test\Unit\Model\Tombstone;

use CommerceLeague\ActiveCampaign\Gateway\Client;
use CommerceLeague\ActiveCampaign\Helper\Config;
use CommerceLeague\ActiveCampaign\Model\Tombstone\TombstoneRelinker;
use CommerceLeague\ActiveCampaign\Test\Unit\AbstractTestCase;
use CommerceLeague\ActiveCampaignApi\Api\ContactApiResourceInterface;
use CommerceLeague\ActiveCampaignApi\Api\CustomerApiResourceInterface;
use CommerceLeague\ActiveCampaignApi\Exception\NotFoundHttpException;
use CommerceLeague\ActiveCampaignApi\Paginator\PageInterface;
use PHPUnit\Framework\MockObject\MockObject;

class TombstoneRelinkerTest extends AbstractTestCase
{
    private const CONNECTION_ID  = '7';
    private const ECOM_ID        = 42;
    private const REAL_EMAIL     = 'real@example.com';
    private const EXTERNAL_ID    = 'guest-99';

    /**
     * @var MockObject|Client
     */
    private $client;

    /**
     * @var MockObject|Config
     */
    private $config;

    /**
     * @var MockObject|CustomerApiResourceInterface
     */
    private $customerApi;

    /**
     * @var MockObject|ContactApiResourceInterface
     */
    private $contactApi;

    /**
     * @var TombstoneRelinker
     */
    private $relinker;

    protected function setUp(): void
    {
        $this->client      = $this->createMock(Client::class);
        $this->config      = $this->createMock(Config::class);
        $this->customerApi = $this->createMock(CustomerApiResourceInterface::class);
        $this->contactApi  = $this->createMock(ContactApiResourceInterface::class);

        $this->client->method('getCustomerApi')->willReturn($this->customerApi);
        $this->client->method('getContactApi')->willReturn($this->contactApi);
        $this->config->method('getConnectionId')->willReturn(self::CONNECTION_ID);

        $this->relinker = new TombstoneRelinker($this->client, $this->config);
    }

    /**
     * @param array<int, mixed> $items
     */
    private function page(array $items): MockObject
    {
        $page = $this->createMock(PageInterface::class);
        $page->method('getItems')->willReturn($items);

        return $page;
    }

    public function testNotFoundWhenGetThrows404(): void
    {
        $this->customerApi->expects($this->once())
            ->method('get')
            ->with(self::ECOM_ID)
            ->willThrowException($this->createMock(NotFoundHttpException::class));

        $this->customerApi->expects($this->never())->method('update');

        $result = $this->relinker->relink(self::ECOM_ID, self::REAL_EMAIL, self::EXTERNAL_ID, true);

        $this->assertSame(TombstoneRelinker::RESULT_NOT_FOUND, $result);
    }

    public function testSkippedNotTombstoneWhenEmailNotDeletedPrefix(): void
    {
        $this->customerApi->expects($this->once())
            ->method('get')
            ->with(self::ECOM_ID)
            ->willReturn(['ecomCustomer' => ['email' => self::REAL_EMAIL, 'subscriberid' => 5]]);

        $this->contactApi->expects($this->never())->method('listPerPage');
        $this->customerApi->expects($this->never())->method('update');

        $result = $this->relinker->relink(self::ECOM_ID, self::REAL_EMAIL, self::EXTERNAL_ID, true);

        $this->assertSame(TombstoneRelinker::RESULT_SKIPPED_NOT_TOMBSTONE, $result);
    }

    public function testNoLiveContactWhenContactSearchEmpty(): void
    {
        $this->customerApi->expects($this->once())
            ->method('get')
            ->with(self::ECOM_ID)
            ->willReturn(['ecomCustomer' => ['email' => 'deleted+12@example.com', 'subscriberid' => null]]);

        $this->contactApi->expects($this->once())
            ->method('listPerPage')
            ->with(1, 0, ['filters' => ['email' => self::REAL_EMAIL]])
            ->willReturn($this->page([]));

        $this->customerApi->expects($this->never())->method('update');

        $result = $this->relinker->relink(self::ECOM_ID, self::REAL_EMAIL, self::EXTERNAL_ID, true);

        $this->assertSame(TombstoneRelinker::RESULT_NO_LIVE_CONTACT, $result);
    }

    public function testSkippedConflictWhenAnotherCustomerHoldsEmail(): void
    {
        $this->customerApi->expects($this->once())
            ->method('get')
            ->with(self::ECOM_ID)
            ->willReturn(['ecomCustomer' => ['email' => 'deleted+12@example.com', 'subscriberid' => null]]);

        $this->contactApi->expects($this->once())
            ->method('listPerPage')
            ->willReturn($this->page([['id' => '500']]));

        // A different ecomCustomer id already holds the real email.
        $this->customerApi->expects($this->once())
            ->method('listPerPage')
            ->with(1, 0, ['filters' => ['email' => self::REAL_EMAIL, 'connectionid' => self::CONNECTION_ID]])
            ->willReturn($this->page([['id' => '999']]));

        $this->customerApi->expects($this->never())->method('update');

        $result = $this->relinker->relink(self::ECOM_ID, self::REAL_EMAIL, self::EXTERNAL_ID, true);

        $this->assertSame(TombstoneRelinker::RESULT_SKIPPED_CONFLICT, $result);
    }

    public function testRelinkedWhenSameIdHoldsEmailDoesNotConflict(): void
    {
        $this->customerApi->expects($this->once())
            ->method('get')
            ->willReturn(['ecomCustomer' => ['email' => 'deleted+12@example.com', 'subscriberid' => null]]);

        $this->contactApi->expects($this->once())
            ->method('listPerPage')
            ->willReturn($this->page([['id' => '500']]));

        // The same ecomCustomer id is returned -> not a conflict.
        $this->customerApi->expects($this->once())
            ->method('listPerPage')
            ->willReturn($this->page([['id' => (string)self::ECOM_ID]]));

        $this->customerApi->expects($this->once())
            ->method('update')
            ->with(
                self::ECOM_ID,
                [
                    'ecomCustomer' => [
                        'connectionid' => self::CONNECTION_ID,
                        'externalid'   => self::EXTERNAL_ID,
                        'email'        => self::REAL_EMAIL,
                    ],
                ]
            )
            ->willReturn([]);

        $result = $this->relinker->relink(self::ECOM_ID, self::REAL_EMAIL, self::EXTERNAL_ID, true);

        $this->assertSame(TombstoneRelinker::RESULT_RELINKED, $result);
    }

    public function testRelinkedDryRunDoesNotCallUpdate(): void
    {
        $this->customerApi->expects($this->once())
            ->method('get')
            ->willReturn(['ecomCustomer' => ['email' => 'deleted+12@example.com', 'subscriberid' => null]]);

        $this->contactApi->expects($this->once())
            ->method('listPerPage')
            ->willReturn($this->page([['id' => '500']]));

        $this->customerApi->expects($this->once())
            ->method('listPerPage')
            ->willReturn($this->page([]));

        $this->customerApi->expects($this->never())->method('update');

        $result = $this->relinker->relink(self::ECOM_ID, self::REAL_EMAIL, self::EXTERNAL_ID, false);

        $this->assertSame(TombstoneRelinker::RESULT_RELINKED, $result);
    }

    public function testRelinkedCommitSendsExactPayload(): void
    {
        $this->customerApi->expects($this->once())
            ->method('get')
            ->willReturn(['ecomCustomer' => ['email' => 'deleted+7@example.com', 'subscriberid' => null]]);

        $this->contactApi->expects($this->once())
            ->method('listPerPage')
            ->willReturn($this->page([['id' => '500']]));

        // No customer currently holds the real email -> free to relink.
        $this->customerApi->expects($this->once())
            ->method('listPerPage')
            ->willReturn($this->page([]));

        $this->customerApi->expects($this->once())
            ->method('update')
            ->with(
                self::ECOM_ID,
                [
                    'ecomCustomer' => [
                        'connectionid' => self::CONNECTION_ID,
                        'externalid'   => self::EXTERNAL_ID,
                        'email'        => self::REAL_EMAIL,
                    ],
                ]
            )
            ->willReturn([]);

        $result = $this->relinker->relink(self::ECOM_ID, self::REAL_EMAIL, self::EXTERNAL_ID, true);

        $this->assertSame(TombstoneRelinker::RESULT_RELINKED, $result);
    }
}
