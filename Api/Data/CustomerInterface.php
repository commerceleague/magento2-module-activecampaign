<?php
/**
 */

namespace CommerceLeague\ActiveCampaign\Api\Data;

/**
 * Interface CustomerInterface
 */
interface CustomerInterface
{
    public const ENTITY_ID = 'entity_id';
    public const EMAIL = 'email';
    public const ACTIVE_CAMPAIGN_ID = 'activecampaign_id';
    public const CREATED_AT = 'created_at';
    public const UPDATED_AT = 'updated_at';

    /**
     * @return int|null
     */
    public function getId();

    /**
     * @param int $id
     * @return CustomerInterface
     */
    public function setId($id);

    /**
     * @return string|null
     */
    public function getEmail();

    /**
     * @param string $email
     * @return CustomerInterface
     */
    public function setEmail($email): self;

    /**
     * @return int|null
     */
    public function getActiveCampaignId();

    /**
     * @param int $activeCampaignId
     * @return CustomerInterface
     */
    public function setActiveCampaignId($activeCampaignId): self;

    /**
     * @return string|null
     */
    public function getCreatedAt();

    /**
     * @param string $createdAt
     * @return CustomerInterface
     */
    public function setCreatedAt($createdAt): self;

    /**
     * @return string|null
     */
    public function getUpdatedAt();

    /**
     * @param string $updatedAt
     * @return CustomerInterface
     */
    public function setUpdatedAt($updatedAt): self;
}
