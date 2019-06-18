<?php
declare(strict_types=1);
/**
 */

namespace CommerceLeague\ActiveCampaign\Api\Data;

/**
 * Interface ContactInterface
 */
interface ContactInterface
{
    public const CONTACT_ID = 'contact_id';
    public const CUSTOMER_ID = 'customer_id';
    public const ACTIVE_CAMPAIGN_ID = 'active_campaign_id';

    /**
     * @return int|null
     */
    public function getId();

    /**
     * @param int $id
     * @return ContactInterface
     */
    public function setId($id);

    /**
     * @return int|null
     */
    public function getCustomerId();

    /**
     * @param int $customerId
     * @return ContactInterface
     */
    public function setCustomerId($customerId): self;

    /**
     * @return int|null
     */
    public function getActiveCampaignId();

    /**
     * @param int $activeCampaignId
     * @return ContactInterface
     */
    public function setActiveCampaignId($activeCampaignId): self;
}
