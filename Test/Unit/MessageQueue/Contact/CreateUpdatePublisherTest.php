<?php
/**
 */

namespace CommerceLeague\ActiveCampaign\Test\Unit\MessageQueue\Contact;

use CommerceLeague\ActiveCampaign\Api\Data\ContactInterface;
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
        $this->createUpdatePublisher = new CreateUpdatePublisher(
            $this->publisher
        );
    }

    public function testExecute()
    {
        /** @var MockObject|ContactInterface $contact */
        $contact = $this->createMock(ContactInterface::class);
        $this->publisher->expects($this->once())
            ->method('publish')
            ->with(CreateUpdatePublisher::TOPIC_NAME, $contact);
        $this->createUpdatePublisher->execute($contact);
    }
}
