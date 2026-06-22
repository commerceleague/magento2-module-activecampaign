<?php
/**
 */

namespace CommerceLeague\ActiveCampaign\Test\Unit\Model\ActiveCampaign;

use CommerceLeague\ActiveCampaign\Api\Data\GuestCustomerInterface;
use CommerceLeague\ActiveCampaign\Test\Unit\AbstractTestCase;
use CommerceLeague\ActiveCampaign\Model\ActiveCampaign\GuestCustomer;
use CommerceLeague\ActiveCampaign\Model\ResourceModel\ActiveCampaign\GuestCustomer as CustomerResource;
use Magento\Framework\Model\Context;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use PHPUnit\Framework\MockObject\MockObject;

class GuestCustomerTest extends AbstractTestCase
{

    /**
     * @var MockObject|Context
     */
    protected $context;

    /**
     * @var MockObject|CustomerResource
     */
    protected $resource;

    /**
     * @var GuestCustomer
     */
    protected $GuestCustomer;

    public function testGetId()
    {
        $entityId = 123;
        $this->GuestCustomer->setData(GuestCustomerInterface::ENTITY_ID, $entityId);
        $this->assertEquals($entityId, $this->GuestCustomer->getId());
    }

    public function testSetId()
    {
        $entityId = 123;
        $this->GuestCustomer->setId($entityId);
        $this->assertEquals($entityId, $this->GuestCustomer->getData(GuestCustomerInterface::ENTITY_ID));
    }

    public function testGetActiveCampaignId()
    {
        $activeCampaignId = 123;
        $this->GuestCustomer->setData(GuestCustomerInterface::ACTIVE_CAMPAIGN_ID, $activeCampaignId);
        $this->assertEquals($activeCampaignId, $this->GuestCustomer->getActiveCampaignId());
    }

    public function testSetActiveCampaignId()
    {
        $activeCampaignId = 123;
        $this->GuestCustomer->setActiveCampaignId($activeCampaignId);
        $this->assertEquals(
            $activeCampaignId, $this->GuestCustomer->getData(GuestCustomerInterface::ACTIVE_CAMPAIGN_ID)
        );
    }

    public function testGetExportAttempts()
    {
        $exportAttempts = 3;
        $this->GuestCustomer->setData(GuestCustomerInterface::EXPORT_ATTEMPTS, $exportAttempts);
        $this->assertSame($exportAttempts, $this->GuestCustomer->getExportAttempts());
    }

    public function testSetExportAttempts()
    {
        $exportAttempts = 3;
        $this->GuestCustomer->setExportAttempts($exportAttempts);
        $this->assertEquals($exportAttempts, $this->GuestCustomer->getData(GuestCustomerInterface::EXPORT_ATTEMPTS));
    }

    public function testGetLastErrorCode()
    {
        $lastErrorCode = 'HTTP_500';
        $this->GuestCustomer->setData(GuestCustomerInterface::LAST_ERROR_CODE, $lastErrorCode);
        $this->assertEquals($lastErrorCode, $this->GuestCustomer->getLastErrorCode());
    }

    public function testSetLastErrorCode()
    {
        $lastErrorCode = 'HTTP_500';
        $this->GuestCustomer->setLastErrorCode($lastErrorCode);
        $this->assertEquals($lastErrorCode, $this->GuestCustomer->getData(GuestCustomerInterface::LAST_ERROR_CODE));
    }

    public function testGetLastErrorMessage()
    {
        $lastErrorMessage = 'Something went wrong';
        $this->GuestCustomer->setData(GuestCustomerInterface::LAST_ERROR_MESSAGE, $lastErrorMessage);
        $this->assertEquals($lastErrorMessage, $this->GuestCustomer->getLastErrorMessage());
    }

    public function testSetLastErrorMessage()
    {
        $lastErrorMessage = 'Something went wrong';
        $this->GuestCustomer->setLastErrorMessage($lastErrorMessage);
        $this->assertEquals(
            $lastErrorMessage,
            $this->GuestCustomer->getData(GuestCustomerInterface::LAST_ERROR_MESSAGE)
        );
    }

    public function testGetLastAttemptedAt()
    {
        $lastAttemptedAt = '2019-01-01 00:00:00';
        $this->GuestCustomer->setData(GuestCustomerInterface::LAST_ATTEMPTED_AT, $lastAttemptedAt);
        $this->assertEquals($lastAttemptedAt, $this->GuestCustomer->getLastAttemptedAt());
    }

    public function testSetLastAttemptedAt()
    {
        $lastAttemptedAt = '2019-01-01 00:00:00';
        $this->GuestCustomer->setLastAttemptedAt($lastAttemptedAt);
        $this->assertEquals(
            $lastAttemptedAt,
            $this->GuestCustomer->getData(GuestCustomerInterface::LAST_ATTEMPTED_AT)
        );
    }

    public function testGetExportStatus()
    {
        $exportStatus = GuestCustomerInterface::EXPORT_STATUS_FAILED;
        $this->GuestCustomer->setData(GuestCustomerInterface::EXPORT_STATUS, $exportStatus);
        $this->assertSame($exportStatus, $this->GuestCustomer->getExportStatus());
    }

    public function testSetExportStatus()
    {
        $exportStatus = GuestCustomerInterface::EXPORT_STATUS_SYNCED;
        $this->GuestCustomer->setExportStatus($exportStatus);
        $this->assertEquals($exportStatus, $this->GuestCustomer->getData(GuestCustomerInterface::EXPORT_STATUS));
    }

    public function testGetCreatedAt()
    {
        $createdAt = '2019-01-01 00:00:00';
        $this->GuestCustomer->setData(GuestCustomerInterface::CREATED_AT, $createdAt);
        $this->assertEquals($createdAt, $this->GuestCustomer->getCreatedAt());
    }

    public function testSetCreatedAt()
    {
        $createdAt = '2019-01-01 00:00:00';
        $this->GuestCustomer->setCreatedAt($createdAt);
        $this->assertEquals($createdAt, $this->GuestCustomer->getData(GuestCustomerInterface::CREATED_AT));
    }

    public function testGetUpdatedAt()
    {
        $updatedAt = '2019-01-01 00:00:00';
        $this->GuestCustomer->setData(GuestCustomerInterface::UPDATED_AT, $updatedAt);
        $this->assertEquals($updatedAt, $this->GuestCustomer->getUpdatedAt());
    }

    public function testSetUpdatedAt()
    {
        $updatedAt = '2019-01-01 00:00:00';
        $this->GuestCustomer->setUpdatedAt($updatedAt);
        $this->assertEquals($updatedAt, $this->GuestCustomer->getData(GuestCustomerInterface::UPDATED_AT));
    }

    protected function setUp(): void
    {
        $this->context  = $this->createMock(Context::class);
        $this->resource = $this->createMock(CustomerResource::class);

        $this->GuestCustomer = (new ObjectManager($this))->getObject(
            GuestCustomer::class,
            [
                'context'  => $this->context,
                'resource' => $this->resource
            ]
        );
    }
}
