<?php
/**
 */

namespace CommerceLeague\ActiveCampaign\Test\Unit\Model\ActiveCampaign;

use CommerceLeague\ActiveCampaign\Api\Data\ContactInterface;
use CommerceLeague\ActiveCampaign\Test\Unit\AbstractTestCase;
use CommerceLeague\ActiveCampaign\Model\ActiveCampaign\Contact;
use CommerceLeague\ActiveCampaign\Model\ResourceModel\ActiveCampaign\Contact as ContactResource;
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

    protected function setUp(): void
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

    public function testGetExportAttempts()
    {
        $exportAttempts = 3;
        $this->contact->setData(ContactInterface::EXPORT_ATTEMPTS, $exportAttempts);
        $this->assertSame($exportAttempts, $this->contact->getExportAttempts());
    }

    public function testSetExportAttempts()
    {
        $exportAttempts = 3;
        $this->contact->setExportAttempts($exportAttempts);
        $this->assertEquals($exportAttempts, $this->contact->getData(ContactInterface::EXPORT_ATTEMPTS));
    }

    public function testGetLastErrorCode()
    {
        $lastErrorCode = 'HTTP_500';
        $this->contact->setData(ContactInterface::LAST_ERROR_CODE, $lastErrorCode);
        $this->assertEquals($lastErrorCode, $this->contact->getLastErrorCode());
    }

    public function testSetLastErrorCode()
    {
        $lastErrorCode = 'HTTP_500';
        $this->contact->setLastErrorCode($lastErrorCode);
        $this->assertEquals($lastErrorCode, $this->contact->getData(ContactInterface::LAST_ERROR_CODE));
    }

    public function testGetLastErrorMessage()
    {
        $lastErrorMessage = 'Something went wrong';
        $this->contact->setData(ContactInterface::LAST_ERROR_MESSAGE, $lastErrorMessage);
        $this->assertEquals($lastErrorMessage, $this->contact->getLastErrorMessage());
    }

    public function testSetLastErrorMessage()
    {
        $lastErrorMessage = 'Something went wrong';
        $this->contact->setLastErrorMessage($lastErrorMessage);
        $this->assertEquals($lastErrorMessage, $this->contact->getData(ContactInterface::LAST_ERROR_MESSAGE));
    }

    public function testGetLastAttemptedAt()
    {
        $lastAttemptedAt = '2019-01-01 00:00:00';
        $this->contact->setData(ContactInterface::LAST_ATTEMPTED_AT, $lastAttemptedAt);
        $this->assertEquals($lastAttemptedAt, $this->contact->getLastAttemptedAt());
    }

    public function testSetLastAttemptedAt()
    {
        $lastAttemptedAt = '2019-01-01 00:00:00';
        $this->contact->setLastAttemptedAt($lastAttemptedAt);
        $this->assertEquals($lastAttemptedAt, $this->contact->getData(ContactInterface::LAST_ATTEMPTED_AT));
    }

    public function testGetExportStatus()
    {
        $exportStatus = ContactInterface::EXPORT_STATUS_FAILED;
        $this->contact->setData(ContactInterface::EXPORT_STATUS, $exportStatus);
        $this->assertSame($exportStatus, $this->contact->getExportStatus());
    }

    public function testSetExportStatus()
    {
        $exportStatus = ContactInterface::EXPORT_STATUS_SYNCED;
        $this->contact->setExportStatus($exportStatus);
        $this->assertEquals($exportStatus, $this->contact->getData(ContactInterface::EXPORT_STATUS));
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
