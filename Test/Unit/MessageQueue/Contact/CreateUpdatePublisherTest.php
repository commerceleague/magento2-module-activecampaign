<?php
declare(strict_types=1);
/**
 */

namespace CommerceLeague\ActiveCampaign\Test\Unit\MessageQueue\Contact;


use CommerceLeague\ActiveCampaign\MessageQueue\Contact\CreateUpdateMessage;
use CommerceLeague\ActiveCampaign\MessageQueue\Contact\CreateUpdatePublisher;
use Magento\Framework\MessageQueue\PublisherInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class CreateUpdatePublisherTest extends TestCase
{
    /**
     * @var MockObject|PublisherInterface
     */
    protected $publisher;

    /**
     * @var CreateUpdatePublisher
     */
    protected $createUpdatePublisher;

    protected function setUp()
    {
        $this->publisher = $this->createMock(PublisherInterface::class);
        $this->createUpdatePublisher = new CreateUpdatePublisher($this->publisher);
    }

    public function testPublish()
    {
        /** @var MockObject|CreateUpdateMessage $message */
        $message = $this->createMock(CreateUpdateMessage::class);

        $this->publisher->expects($this->once())
            ->method('publish')
            ->with('activecampaign.contact.create_update', $message);

        $this->createUpdatePublisher->publish($message);
    }
}
