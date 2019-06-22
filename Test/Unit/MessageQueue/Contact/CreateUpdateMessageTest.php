<?php
/**
 */

namespace CommerceLeague\ActiveCampaign\Test\Unit\MessageQueue\Contact;

use CommerceLeague\ActiveCampaign\MessageQueue\Contact\CreateUpdateMessage;
use PHPUnit\Framework\TestCase;

class CreateUpdateMessageTest extends TestCase
{
    public function testGetSetContactId()
    {
        $contactId = 123;
        $createUpdateMessage = new CreateUpdateMessage();
        $createUpdateMessage->setContactId($contactId);
        $this->assertEquals($contactId, $createUpdateMessage->getContactId());
    }

    public function testGetSetSerializedRequest()
    {
        $serializedRequest = json_encode(['request']);
        $createUpdateMessage = new CreateUpdateMessage();
        $createUpdateMessage->setSerializedRequest($serializedRequest);
        $this->assertEquals($serializedRequest, $createUpdateMessage->getSerializedRequest());
    }

    public function testBuild()
    {
        $contactId = 123;
        $request = ['request'];
        $createUpdateMessage = CreateUpdateMessage::build($contactId, $request);

        $this->assertEquals($contactId, $createUpdateMessage->getContactId());
        $this->assertEquals(json_encode($request), $createUpdateMessage->getSerializedRequest());
    }
}
