<?php
declare(strict_types=1);
/**
 */

namespace CommerceLeague\ActiveCampaign\MessageQueue\Customer;

use CommerceLeague\ActiveCampaign\MessageQueue\EntityIdAwareInterface;
use CommerceLeague\ActiveCampaign\MessageQueue\EntityIdAwareTrait;
use CommerceLeague\ActiveCampaign\MessageQueue\SerializedRequestAwareInterface;
use CommerceLeague\ActiveCampaign\MessageQueue\SerializedRequestAwareTrait;

/**
 * Class CreateMessage
 */
class CreateMessage implements
    EntityIdAwareInterface,
    SerializedRequestAwareInterface
{
    use SerializedRequestAwareTrait;
    use EntityIdAwareTrait;
}
