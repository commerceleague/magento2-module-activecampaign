<?php
/**
 */

namespace CommerceLeague\ActiveCampaign\Api\Data;

/**
 * Interface GuestCustomerInterface
 */
interface GuestCustomerInterface
{

    public const ENTITY_ID          = 'entity_id';
    public const ACTIVE_CAMPAIGN_ID = 'activecampaign_id';
    public const EMAIL              = 'email';
    public const FIRSTNAME          = 'firstname';
    public const LASTNAME           = 'lastname';
    public const CREATED_AT         = 'created_at';
    public const UPDATED_AT         = 'updated_at';

    /**
     * @return int|null
     */
    public function getId();

    /**
     * @param int $id
     *
     * @return GuestCustomerInterface
     */
    public function setId($id);

    /**
     * @return int|null
     */
    public function getActiveCampaignId();

    /**
     * @param int $activeCampaignId
     *
     * @return GuestCustomerInterface
     */
    public function setActiveCampaignId($activeCampaignId): self;

    /**
     * @return string|null
     */
    public function getEmail();

    /**
     * @param string $id
     *
     * @return GuestCustomerInterface
     */
    public function setEmail($email);

    /**
     * @return string|null
     */
    public function getFirstname();

    /**
     * @param string $id
     *
     * @return GuestCustomerInterface
     */
    public function setFirstname($firstname);

    /**
     * @return string|null
     */
    public function getLastname();

    /**
     * @param string $id
     *
     * @return GuestCustomerInterface
     */
    public function setLastname($lastname);

    /**
     * @return string|null
     */
    public function getCreatedAt();

    /**
     * @param string $createdAt
     *
     * @return GuestCustomerInterface
     */
    public function setCreatedAt($createdAt): self;

    /**
     * @return string|null
     */
    public function getUpdatedAt();

    /**
     * @param string $updatedAt
     *
     * @return GuestCustomerInterface
     */
    public function setUpdatedAt($updatedAt): self;
}
