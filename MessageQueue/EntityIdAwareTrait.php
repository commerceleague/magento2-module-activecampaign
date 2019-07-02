<?php
/**
 */

namespace CommerceLeague\ActiveCampaign\MessageQueue;

/**
 * Trait EntityAwareTrait
 */
trait EntityIdAwareTrait
{
    /**
     * @var int
     */
    protected $entityId;

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
    public function setEntityId(int $entityId)
    {
        $this->entityId = $entityId;
        return $this;
    }
}