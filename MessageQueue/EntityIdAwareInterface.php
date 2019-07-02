<?php
/**
 */

namespace CommerceLeague\ActiveCampaign\MessageQueue;

/**
 * Interface EntityAwareInterface
 */
interface EntityIdAwareInterface
{
    /**
     * @return int
     */
    public function getEntityId(): int;

    /**
     * @param int $entityId
     * @return EntityIdAwareInterface
     */
    public function setEntityId(int $entityId);
}
