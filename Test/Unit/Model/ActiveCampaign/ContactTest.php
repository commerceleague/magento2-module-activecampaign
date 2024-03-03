<?php
/**
 */

namespace CommerceLeague\ActiveCampaign\Test\Unit\Model\ActiveCampaign;

use CommerceLeague\ActiveCampaign\Api\Data\ContactInterface;
use CommerceLeague\ActiveCampaign\Test\Unit\AbstractTestCase;
use CommerceLeague\ActiveCampaign\vendor\Model\ActiveCampaign\Contact;
use CommerceLeague\ActiveCampaign\vendor\Model\ResourceModel\ActiveCampaign\Contact as ContactResource;
use Magento\Framework\Model\Context;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use PHPUnit\Framework\MockObject\MockObject;

class ContactTest extends AbstractTestCase
{

    /**
     * @var MockObject|Context
     */
    protected $context;

    /**
     * @var MockObject|ContactResource
     */
    protected $resource;

    /**
     * @var Contact
     */
    protected $contact;

    protected function setUp()
    {
        $this->context = $this->createMock(Context::class);
        $this->resource = $this->createMock(ContactResource::class);

        $this->contact = (new ObjectManager($this))->getObject(
            Contact::class,
            [
                'context' => $this->context,
                'resource' => $this->resource
            ]
        );
    }

    public function testGetId()
    {
        $entityId = 123;
        $this->contact->setData(ContactInterface::ENTITY_ID, $entityId);
        $this->assertEquals($entityId, $this->contact->getId());
    }

    public function testSetId()
    {
        $entityId = 123;
        $this->contact->setId($entityId);
        $this->assertEquals($entityId, $this->contact->getData(ContactInterface::ENTITY_ID));
    }

    public function testGetEmail()
    {
        $email = 'example@example.com';
        $this->contact->setData(ContactInterface::EMAIL, $email);
        $this->assertEquals($email, $this->contact->getEmail());
    }

    public function testSetEmail()
    {
        $email = 'example@example.com';
        $this->contact->setEmail($email);
        $this->assertEquals($email, $this->contact->getData(ContactInterface::EMAIL));
    }

    public function testGetActiveCampaignId()
    {
        $activeCampaignId = 123;
        $this->contact->setData(ContactInterface::ACTIVE_CAMPAIGN_ID, $activeCampaignId);
        $this->assertEquals($activeCampaignId, $this->contact->getActiveCampaignId());
    }

    public function testSetActiveCampaignId()
    {
        $activeCampaignId = 123;
        $this->contact->setActiveCampaignId($activeCampaignId);
        $this->assertEquals($activeCampaignId, $this->contact->getData(ContactInterface::ACTIVE_CAMPAIGN_ID));
    }

    public function testGetCreatedAt()
    {
        $createdAt = '2019-01-01 00:00:00';
        $this->contact->setData(ContactInterface::CREATED_AT, $createdAt);
        $this->assertEquals($createdAt, $this->contact->getCreatedAt());
    }

    public function testSetCreatedAt()
    {
        $createdAt = '2019-01-01 00:00:00';
        $this->contact->setCreatedAt($createdAt);
        $this->assertEquals($createdAt, $this->contact->getData(ContactInterface::CREATED_AT));
    }

    public function testGetUpdatedAt()
    {
        $updatedAt = '2019-01-01 00:00:00';
        $this->contact->setData(ContactInterface::UPDATED_AT, $updatedAt);
        $this->assertEquals($updatedAt, $this->contact->getUpdatedAt());
    }

    public function testSetUpdatedAt()
    {
        $updatedAt = '2019-01-01 00:00:00';
        $this->contact->setUpdatedAt($updatedAt);
        $this->assertEquals($updatedAt, $this->contact->getData(ContactInterface::UPDATED_AT));
    }
}
