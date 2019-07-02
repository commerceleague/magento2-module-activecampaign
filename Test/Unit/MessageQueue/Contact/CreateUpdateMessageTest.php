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
        $entityId = 123;
        $createUpdateMessage = new CreateUpdateMessage();
        $createUpdateMessage->setEntityId($entityId);
        $this->assertEquals($entityId, $createUpdateMessage->getEntityId());
    }

    public function testGetSetSerializedRequest()
    {
        $serializedRequest = json_encode(['request']);
        $createUpdateMessage = new CreateUpdateMessage();
        $createUpdateMessage->setSerializedRequest($serializedRequest);
        $this->assertEquals($serializedRequest, $createUpdateMessage->getSerializedRequest());
    }
}
