<?php
declare(strict_types=1);
/**
 */

namespace CommerceLeague\ActiveCampaign\Test\Unit\Model;

use CommerceLeague\ActiveCampaign\Api\Data\AbandonedInterface;
use CommerceLeague\ActiveCampaign\Model\Abandoned;
use CommerceLeague\ActiveCampaign\Model\ResourceModel\Abandoned as AbandonedResource;
use Magento\Framework\Model\Context;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class AbandonedTest extends TestCase
{
    /**
     * @var MockObject|Context
     */
    protected $context;

    /**
     * @var MockObject|AbandonedResource
     */
    protected $resource;

    /**
     * @var Abandoned
     */
    protected $abandoned;

    protected function setUp()
    {
        $this->context = $this->createMock(Context::class);
        $this->resource = $this->createMock(AbandonedResource::class);

        $this->abandoned = (new ObjectManager($this))->getObject(
            Abandoned::class,
            [
                'context' => $this->context,
                'resource' => $this->resource
            ]
        );
    }

    public function testGetId()
    {
        $entityId = 123;
        $this->abandoned->setData(AbandonedInterface::ENTITY_ID, $entityId);
        $this->assertEquals($entityId, $this->abandoned->getId());
    }

    public function testSetId()
    {
        $entityId = 123;
        $this->abandoned->setId($entityId);
        $this->assertEquals($entityId, $this->abandoned->getData(AbandonedInterface::ENTITY_ID));
    }

    public function testSetQuoteId()
    {
        $quoteId = 123;
        $this->abandoned->setData(AbandonedInterface::QUOTE_ID, $quoteId);
        $this->assertEquals($quoteId, $this->abandoned->getQuoteId());
    }

    public function testGetQuoteId()
    {
        $quoteId = 123;
        $this->abandoned->setQuoteId($quoteId);
        $this->assertEquals($quoteId, $this->abandoned->getData(AbandonedInterface::QUOTE_ID));
    }

    public function testGetActiveCampaignId()
    {
        $activeCampaignId = 123;
        $this->abandoned->setData(AbandonedInterface::ACTIVE_CAMPAIGN_ID, $activeCampaignId);
        $this->assertEquals($activeCampaignId, $this->abandoned->getActiveCampaignId());
    }

    public function testSetActiveCampaignId()
    {
        $activeCampaignId = 123;
        $this->abandoned->setActiveCampaignId($activeCampaignId);
        $this->assertEquals($activeCampaignId, $this->abandoned->getData(AbandonedInterface::ACTIVE_CAMPAIGN_ID));
    }

    public function testGetCreatedAt()
    {
        $createdAt = '2019-01-01 00:00:00';
        $this->abandoned->setData(AbandonedInterface::CREATED_AT, $createdAt);
        $this->assertEquals($createdAt, $this->abandoned->getCreatedAt());
    }

    public function testSetCreatedAt()
    {
        $createdAt = '2019-01-01 00:00:00';
        $this->abandoned->setCreatedAt($createdAt);
        $this->assertEquals($createdAt, $this->abandoned->getData(AbandonedInterface::CREATED_AT));
    }

    public function testGetUpdatedAt()
    {
        $updatedAt = '2019-01-01 00:00:00';
        $this->abandoned->setData(AbandonedInterface::UPDATED_AT, $updatedAt);
        $this->assertEquals($updatedAt, $this->abandoned->getUpdatedAt());
    }

    public function testSetUpdatedAt()
    {
        $updatedAt = '2019-01-01 00:00:00';
        $this->abandoned->setUpdatedAt($updatedAt);
        $this->assertEquals($updatedAt, $this->abandoned->getData(AbandonedInterface::UPDATED_AT));
    }

}
