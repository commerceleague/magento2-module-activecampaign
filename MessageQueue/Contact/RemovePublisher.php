<?php
declare(strict_types=1);
/**
 */
namespace CommerceLeague\ActiveCampaign\MessageQueue\Contact;

use Magento\Framework\MessageQueue\PublisherInterface;

/**
 * Class RemovePublisher
 */
class RemovePublisher
{
    public const TOPIC_NAME = 'activecampaign.contact.remove';

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
     * @param RemoveMessage $message
     */
    public function execute(RemoveMessage $message): void
    {
        $this->publisher->publish(self::TOPIC_NAME, $message);
    }
}

