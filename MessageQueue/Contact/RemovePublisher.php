<?php
declare(strict_types=1);
/**
 */
namespace CommerceLeague\ActiveCampaign\MessageQueue\Contact;

use CommerceLeague\ActiveCampaign\Api\Data\ContactInterface;
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
     * @param ContactInterface $contact
     */
    public function execute(ContactInterface $contact): void
    {
        $this->publisher->publish(self::TOPIC_NAME, $contact);
    }
}

