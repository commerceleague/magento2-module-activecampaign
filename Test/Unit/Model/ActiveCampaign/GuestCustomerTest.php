<?php
/**
 */

namespace CommerceLeague\ActiveCampaign\Test\Unit\Model\ActiveCampaign;

use CommerceLeague\ActiveCampaign\Api\Data\GuestCustomerInterface;
use CommerceLeague\ActiveCampaign\Model\ActiveCampaign\GuestCustomer;
use CommerceLeague\ActiveCampaign\Model\ResourceModel\ActiveCampaign\GuestCustomer as CustomerResource;
use CommerceLeague\ActiveCampaign\Test\Unit\AbstractTestCase;
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
    protected $customer;

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
