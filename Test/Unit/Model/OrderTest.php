<?php
declare(strict_types=1);
/**
 */

namespace CommerceLeague\ActiveCampaign\Test\Unit\Model;

use CommerceLeague\ActiveCampaign\Api\Data\OrderInterface;
use CommerceLeague\ActiveCampaign\Model\Order;
use CommerceLeague\ActiveCampaign\Model\ResourceModel\Order as OrderResource;
use Magento\Framework\Model\Context;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class OrderTest extends TestCase
{
    /**
     * @var MockObject|Context
     */
    protected $context;

    /**
     * @var MockObject|OrderResource
     */
    protected $resource;

    /**
     * @var Order
     */
    protected $order;

    protected function setUp()
    {
        $this->context = $this->createMock(Context::class);
        $this->resource = $this->createMock(OrderResource::class);

        $this->order = (new ObjectManager($this))->getObject(
            Order::class,
            [
                'context' => $this->context,
                'resource' => $this->resource
            ]
        );
    }

    public function testGetId()
    {
        $entityId = 123;
        $this->order->setData(OrderInterface::ENTITY_ID, $entityId);
        $this->assertEquals($entityId, $this->order->getId());
    }

    public function testSetId()
    {
        $entityId = 123;
        $this->order->setId($entityId);
        $this->assertEquals($entityId, $this->order->getData(OrderInterface::ENTITY_ID));
    }

    public function testGetMagentoOrderId()
    {
        $magentoOrderId = 123;
        $this->order->setData(OrderInterface::MAGENTO_ORDER_ID, $magentoOrderId);
        $this->assertEquals($magentoOrderId, $this->order->getMagentoOrderId());
    }

    public function testSetMagentoOrderId()
    {
        $magentoOrderId = 123;
        $this->order->setMagentoOrderId($magentoOrderId);
        $this->assertEquals($magentoOrderId, $this->order->getData(OrderInterface::MAGENTO_ORDER_ID));
    }

    public function testGetActiveCampaignId()
    {
        $activeCampaignId = 123;
        $this->order->setData(OrderInterface::ACTIVE_CAMPAIGN_ID, $activeCampaignId);
        $this->assertEquals($activeCampaignId, $this->order->getActiveCampaignId());
    }

    public function testSetActiveCampaignId()
    {
        $activeCampaignId = 123;
        $this->order->setActiveCampaignId($activeCampaignId);
        $this->assertEquals($activeCampaignId, $this->order->getData(OrderInterface::ACTIVE_CAMPAIGN_ID));
    }

    public function testGetCreatedAt()
    {
        $createdAt = '2019-01-01 00:00:00';
        $this->order->setData(OrderInterface::CREATED_AT, $createdAt);
        $this->assertEquals($createdAt, $this->order->getCreatedAt());
    }

    public function testSetCreatedAt()
    {
        $createdAt = '2019-01-01 00:00:00';
        $this->order->setCreatedAt($createdAt);
        $this->assertEquals($createdAt, $this->order->getData(OrderInterface::CREATED_AT));
    }

    public function testGetUpdatedAt()
    {
        $updatedAt = '2019-01-01 00:00:00';
        $this->order->setData(OrderInterface::UPDATED_AT, $updatedAt);
        $this->assertEquals($updatedAt, $this->order->getUpdatedAt());
    }

    public function testSetUpdatedAt()
    {
        $updatedAt = '2019-01-01 00:00:00';
        $this->order->setUpdatedAt($updatedAt);
        $this->assertEquals($updatedAt, $this->order->getData(OrderInterface::UPDATED_AT));
    }

}
