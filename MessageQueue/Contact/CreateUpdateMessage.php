<?php
declare(strict_types=1);
/**
 */
namespace CommerceLeague\ActiveCampaign\MessageQueue\Contact;

use CommerceLeague\ActiveCampaign\MessageQueue\SerializedRequestAwareInterface;
use CommerceLeague\ActiveCampaign\MessageQueue\SerializedRequestAwareTrait;

/**
 * Class CreateUpdateConsumerMessage
 */
class CreateUpdateMessage implements SerializedRequestAwareInterface
{
    use SerializedRequestAwareTrait;

    /**
     * @var int
     */
    private $entityId;

    /**
     * @return int
     */
    public function getEntityId(): int
    {
        return $this->entityId;
    }

    /**
     * @param int $entityId
     * @return $this
     */
    public function setEntityId(int $entityId): self
    {
        $this->entityId = $entityId;
        return $this;
    }
}
