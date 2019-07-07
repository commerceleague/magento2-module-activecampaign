<?php
declare(strict_types=1);
/**
 */

namespace CommerceLeague\ActiveCampaign\Api\Data;

/**
 * Interface AbandonedInterface
 */
interface AbandonedInterface
{
    public const ENTITY_ID = 'entity_id';
    public const QUOTE_ID = 'quote_id';
    public const ACTIVE_CAMPAIGN_ID = 'activecampaign_id';
    public const CREATED_AT = 'created_at';
    public const UPDATED_AT = 'updated_at';

    /**
     * @return int|null
     */
    public function getId();

    /**
     * @param int $id
     * @return AbandonedInterface
     */
    public function setId($id);

    /**
     * @return int|null
     */
    public function getQuoteId();

    /**
     * @param int $quoteId
     * @return AbandonedInterface
     */
    public function setQuoteId($quoteId): self;

    /**
     * @return int|null
     */
    public function getActiveCampaignId();

    /**
     * @param int $activeCampaignId
     * @return AbandonedInterface
     */
    public function setActiveCampaignId($activeCampaignId): self;

    /**
     * @return string|null
     */
    public function getCreatedAt();

    /**
     * @param string $createdAt
     * @return AbandonedInterface
     */
    public function setCreatedAt($createdAt): self;

    /**
     * @return string|null
     */
    public function getUpdatedAt();

    /**
     * @param string $updatedAt
     * @return AbandonedInterface
     */
    public function setUpdatedAt($updatedAt): self;
}
