<?php
declare(strict_types=1);
/**
 */
namespace CommerceLeague\ActiveCampaign\MessageQueue\Contact;

use CommerceLeague\ActiveCampaign\Api\Data\ContactInterface;

/**
 * Class RemoveConsumer
 */
class RemoveConsumer
{
    /**
     * @param ContactInterface $contact
     */
    public function execute(ContactInterface $contact): void
    {
        // TODO::do something in here
    }
}
