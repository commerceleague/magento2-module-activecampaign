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
    public const MAGENTO_CUSTOMER_ID = 'magento_customer_id';
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
     * @return int|null
     */
    public function getMagentoCustomerId();

    /**
     * @param int $magentoCustomerId
     */
    public function setMagentoCustomerId($magentoCustomerId): self;

    /**
     * @return int|null
     */
    public function getActiveCampaignId();

    /**
     * @param int $activeCampaignId
     */
    public function setActiveCampaignId($activeCampaignId): self;

    /**
     * @return string|null
     */
    public function getCreatedAt();

    /**
     * @param string $createdAt
     */
    public function setCreatedAt($createdAt): self;

    /**
     * @return string|null
     */
    public function getUpdatedAt();

    /**
     * @param string $updatedAt
     */
    public function setUpdatedAt($updatedAt): self;
}
