<?php
/**
 */

namespace Api\Data;

namespace CommerceLeague\ActiveCampaign\Api\Data;

/**
 * Interface ConnectionInterface
 */
interface ConnectionInterface
{
    public const CONNECTION_ID = 'connection_id';
    public const NAME = 'name';
    public const LOGO_URL = 'logo_url';
    public const LINK_URL = 'link_url';
    public const ACTIVE_CAMPAIGN_ID = 'active_campaign_id';
    public const CREATED_AT = 'created_at';
    public const UPDATED_AT = 'updated_at';

    /**
     * @return int|null
     */
    public function getId();

    /**
     * @param int $id
     * @return ConnectionInterface
     */
    public function setId($id);

    /**
     * @return string|null
     */
    public function getName();

    /**
     * @param string $name
     * @return ConnectionInterface
     */
    public function setName($name): self;

    /**
     * @return string|null
     */
    public function getLogoUrl();

    /**
     * @param string $logoUrl
     * @return ConnectionInterface
     */
    public function setLogoUrl($logoUrl): self;

    /**
     * @return string|null
     */
    public function getLinkUrl();

    /**
     * @param string $linkUrl
     * @return ConnectionInterface
     */
    public function setLinkUrl($linkUrl): self;

    /**
     * @return int|null
     */
    public function getActiveCampaignId();

    /**
     * @param int $activeCampaignId
     * @return ConnectionInterface
     */
    public function setActiveCampaignId($activeCampaignId): self;

    /**
     * @return string|null
     */
    public function getCreatedAt();

    /**
     * @param string $createdAt
     * @return ConnectionInterface
     */
    public function setCreatedAt($createdAt): self;

    /**
     * @return string|null
     */
    public function getUpdatedAt();

    /**
     * @param string $updatedAt
     * @return ConnectionInterface
     */
    public function setUpdatedAt($updatedAt): self;
}