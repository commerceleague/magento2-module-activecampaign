<?php
/**
 */

namespace CommerceLeague\ActiveCampaign\Api\Data;

/**
 * Interface OrderInterface
 */
interface OrderInterface
{
    public const ENTITY_ID = 'entity_id';
    public const MAGENTO_ORDER_ID = 'magento_order_id';
    public const MAGENTO_QUOTE_ID = 'magento_quote_id';
    public const ACTIVE_CAMPAIGN_ID = 'activecampaign_id';
    public const CREATED_AT = 'created_at';
    public const UPDATED_AT = 'updated_at';

    /**
     * @return int|null
     */
    public function getId();

    /**
     * @param int $id
     * @return OrderInterface
     */
    public function setId($id);

    /**
     * @return int|null
     */
    public function getMagentoQuoteId();

    /**
     * @param int $magentoQuoteId
     * @return OrderInterface
     */
    public function setMagentoQuoteId($magentoQuoteId): self;

    /**
     * @return int|null
     */
    public function getActiveCampaignId();

    /**
     * @param int $activeCampaignId
     * @return OrderInterface
     */
    public function setActiveCampaignId($activeCampaignId): self;

    /**
     * @return string|null
     */
    public function getCreatedAt();

    /**
     * @param string $createdAt
     * @return OrderInterface
     */
    public function setCreatedAt($createdAt): self;

    /**
     * @return string|null
     */
    public function getUpdatedAt();

    /**
     * @param string $updatedAt
     * @return OrderInterface
     */
    public function setUpdatedAt($updatedAt): self;
}
