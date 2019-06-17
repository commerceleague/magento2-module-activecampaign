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
     * @param RemoveMessage $message
     */
    public function execute(RemoveMessage $message): void
    {
        // TODO::do something in here
    }
}
