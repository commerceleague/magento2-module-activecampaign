<?php
/**
 */

namespace CommerceLeague\ActiveCampaign\Test\Unit\Model\ActiveCampaign;

use CommerceLeague\ActiveCampaign\Api\Data\CustomerInterface;
use CommerceLeague\ActiveCampaign\Test\Unit\AbstractTestCase;
use CommerceLeague\ActiveCampaign\Model\ActiveCampaign\Customer;
use CommerceLeague\ActiveCampaign\Model\ResourceModel\ActiveCampaign\Customer as CustomerResource;
use Magento\Framework\Model\Context;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use PHPUnit\Framework\MockObject\MockObject;

class CustomerTest extends AbstractTestCase
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
     * @var Customer
     */
    protected $customer;

    protected function setUp(): void
    {
        $this->context = $this->createMock(Context::class);
        $this->resource = $this->createMock(CustomerResource::class);

        $this->customer = (new ObjectManager($this))->getObject(
            Customer::class,
            [
                'context' => $this->context,
                'resource' => $this->resource
            ]
        );
    }

    public function testGetId()
    {
        $entityId = 123;
        $this->customer->setData(CustomerInterface::ENTITY_ID, $entityId);
        $this->assertEquals($entityId, $this->customer->getId());
    }

    public function testSetId()
    {
        $entityId = 123;
        $this->customer->setId($entityId);
        $this->assertEquals($entityId, $this->customer->getData(CustomerInterface::ENTITY_ID));
    }

    public function testGetMagentoCustomerId()
    {
        $magentoCustomerId = 123;
        $this->customer->setData(CustomerInterface::MAGENTO_CUSTOMER_ID, $magentoCustomerId);
        $this->assertEquals($magentoCustomerId, $this->customer->getMagentoCustomerId());
    }

    public function testSetMagentoCustomerId()
    {
        $magentoCustomerId = 123;
        $this->customer->setMagentoCustomerId($magentoCustomerId);
        $this->assertEquals($magentoCustomerId, $this->customer->getData(CustomerInterface::MAGENTO_CUSTOMER_ID));
    }

    public function testGetActiveCampaignId()
    {
        $activeCampaignId = 123;
        $this->customer->setData(CustomerInterface::ACTIVE_CAMPAIGN_ID, $activeCampaignId);
        $this->assertEquals($activeCampaignId, $this->customer->getActiveCampaignId());
    }

    public function testSetActiveCampaignId()
    {
        $activeCampaignId = 123;
        $this->customer->setActiveCampaignId($activeCampaignId);
        $this->assertEquals($activeCampaignId, $this->customer->getData(CustomerInterface::ACTIVE_CAMPAIGN_ID));
    }

    public function testGetExportAttempts()
    {
        $exportAttempts = 3;
        $this->customer->setData(CustomerInterface::EXPORT_ATTEMPTS, $exportAttempts);
        $this->assertSame($exportAttempts, $this->customer->getExportAttempts());
    }

    public function testSetExportAttempts()
    {
        $exportAttempts = 3;
        $this->customer->setExportAttempts($exportAttempts);
        $this->assertEquals($exportAttempts, $this->customer->getData(CustomerInterface::EXPORT_ATTEMPTS));
    }

    public function testGetLastErrorCode()
    {
        $lastErrorCode = 'HTTP_500';
        $this->customer->setData(CustomerInterface::LAST_ERROR_CODE, $lastErrorCode);
        $this->assertEquals($lastErrorCode, $this->customer->getLastErrorCode());
    }

    public function testSetLastErrorCode()
    {
        $lastErrorCode = 'HTTP_500';
        $this->customer->setLastErrorCode($lastErrorCode);
        $this->assertEquals($lastErrorCode, $this->customer->getData(CustomerInterface::LAST_ERROR_CODE));
    }

    public function testGetLastErrorMessage()
    {
        $lastErrorMessage = 'Something went wrong';
        $this->customer->setData(CustomerInterface::LAST_ERROR_MESSAGE, $lastErrorMessage);
        $this->assertEquals($lastErrorMessage, $this->customer->getLastErrorMessage());
    }

    public function testSetLastErrorMessage()
    {
        $lastErrorMessage = 'Something went wrong';
        $this->customer->setLastErrorMessage($lastErrorMessage);
        $this->assertEquals($lastErrorMessage, $this->customer->getData(CustomerInterface::LAST_ERROR_MESSAGE));
    }

    public function testGetLastAttemptedAt()
    {
        $lastAttemptedAt = '2019-01-01 00:00:00';
        $this->customer->setData(CustomerInterface::LAST_ATTEMPTED_AT, $lastAttemptedAt);
        $this->assertEquals($lastAttemptedAt, $this->customer->getLastAttemptedAt());
    }

    public function testSetLastAttemptedAt()
    {
        $lastAttemptedAt = '2019-01-01 00:00:00';
        $this->customer->setLastAttemptedAt($lastAttemptedAt);
        $this->assertEquals($lastAttemptedAt, $this->customer->getData(CustomerInterface::LAST_ATTEMPTED_AT));
    }

    public function testGetExportStatus()
    {
        $exportStatus = CustomerInterface::EXPORT_STATUS_FAILED;
        $this->customer->setData(CustomerInterface::EXPORT_STATUS, $exportStatus);
        $this->assertSame($exportStatus, $this->customer->getExportStatus());
    }

    public function testSetExportStatus()
    {
        $exportStatus = CustomerInterface::EXPORT_STATUS_SYNCED;
        $this->customer->setExportStatus($exportStatus);
        $this->assertEquals($exportStatus, $this->customer->getData(CustomerInterface::EXPORT_STATUS));
    }

    public function testGetCreatedAt()
    {
        $createdAt = '2019-01-01 00:00:00';
        $this->customer->setData(CustomerInterface::CREATED_AT, $createdAt);
        $this->assertEquals($createdAt, $this->customer->getCreatedAt());
    }

    public function testSetCreatedAt()
    {
        $createdAt = '2019-01-01 00:00:00';
        $this->customer->setCreatedAt($createdAt);
        $this->assertEquals($createdAt, $this->customer->getData(CustomerInterface::CREATED_AT));
    }

    public function testGetUpdatedAt()
    {
        $updatedAt = '2019-01-01 00:00:00';
        $this->customer->setData(CustomerInterface::UPDATED_AT, $updatedAt);
        $this->assertEquals($updatedAt, $this->customer->getUpdatedAt());
    }

    public function testSetUpdatedAt()
    {
        $updatedAt = '2019-01-01 00:00:00';
        $this->customer->setUpdatedAt($updatedAt);
        $this->assertEquals($updatedAt, $this->customer->getData(CustomerInterface::UPDATED_AT));
    }
}
