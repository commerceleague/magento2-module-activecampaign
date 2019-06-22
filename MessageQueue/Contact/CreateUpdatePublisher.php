<?php
declare(strict_types=1);
/**
 */

namespace CommerceLeague\ActiveCampaign\MessageQueue\Contact;

use Magento\Framework\MessageQueue\PublisherInterface;

/**
 * Class CreateUpdatePublisher
 */
class CreateUpdatePublisher
{
    private const TOPIC = 'activecampaign.contact.create_update';

    /**
     * @var PublisherInterface
     */
    private $publisher;

    /**
     * @param PublisherInterface $publisher
     */
    public function __construct(PublisherInterface $publisher)
    {
        $this->publisher = $publisher;
    }

    /**
     * @param CreateUpdateMessage $message
     */
    public function publish(CreateUpdateMessage $message): void
    {
        $this->publisher->publish(self::TOPIC, $message);
    }
}
