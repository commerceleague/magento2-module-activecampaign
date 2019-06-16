<?php
/**
 */

namespace CommerceLeague\ActiveCampaign\Test\Unit\MessageQueue\Contact;

use CommerceLeague\ActiveCampaign\Api\Data\ContactInterface;
use CommerceLeague\ActiveCampaign\MessageQueue\Contact\RemovePublisher;
use Magento\Framework\MessageQueue\PublisherInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class RemovePublisherTest extends TestCase
{
    /**
     * @var MockObject|PublisherInterface
     */
    protected $publisher;

    /**
     * @var RemovePublisher
     */
    protected $removePublisher;

    protected function setUp()
    {
        $this->publisher = $this->createMock(PublisherInterface::class);
        $this->removePublisher = new RemovePublisher(
            $this->publisher
        );
    }

    public function testExecute()
    {
        /** @var MockObject|ContactInterface $contact */
        $contact = $this->createMock(ContactInterface::class);
        $this->publisher->expects($this->once())
            ->method('publish')
            ->with(RemovePublisher::TOPIC_NAME, $contact);
        $this->removePublisher->execute($contact);
    }
}
