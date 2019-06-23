<?php
/**
 */

namespace CommerceLeague\ActiveCampaign\Test\Unit\Model;

use CommerceLeague\ActiveCampaign\Api\Data\ContactInterface;
use CommerceLeague\ActiveCampaign\Model\Contact;
use CommerceLeague\ActiveCampaign\Model\ResourceModel\Contact as ContactResource;
use Magento\Framework\Model\Context;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;

class ContactTest extends TestCase
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
        $contactId = 123;
        $this->contact->setData(ContactInterface::CONTACT_ID, $contactId);
        $this->assertEquals($contactId, $this->contact->getId());
    }

    public function testSetId()
    {
        $contactId = 123;
        $this->contact->setId($contactId);
        $this->assertEquals($contactId, $this->contact->getData(ContactInterface::CONTACT_ID));
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
}
