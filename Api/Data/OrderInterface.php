<?php
/**
 */

namespace CommerceLeague\ActiveCampaign\Api\Data;

/**
 * Interface OrderInterface
 */
interface OrderInterface extends FailureTrackableInterface
{
    public const ENTITY_ID = 'entity_id';
    public const MAGENTO_ORDER_ID = 'magento_order_id';
    public const MAGENTO_QUOTE_ID = 'magento_quote_id';
    public const ACTIVE_CAMPAIGN_ID = 'activecampaign_id';
    public const EXPORT_ATTEMPTS = 'export_attempts';
    public const LAST_ERROR_CODE = 'last_error_code';
    public const LAST_ERROR_MESSAGE = 'last_error_message';
    public const LAST_ATTEMPTED_AT = 'last_attempted_at';
    public const EXPORT_STATUS = 'export_status';
    public const CREATED_AT = 'created_at';
    public const UPDATED_AT = 'updated_at';

    public const EXPORT_STATUS_PENDING = 0;
    public const EXPORT_STATUS_SYNCED = 1;
    public const EXPORT_STATUS_FAILED = 2;

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
     */
    public function setMagentoQuoteId($magentoQuoteId): self;

    /**
     * @return int|null
     */
    public function getMagentoOrderId();

    /**
     * @param int $magentoOrderId
     */
    public function setMagentoOrderId($magentoOrderId): self;

    /**
     * @return int|null
     */
    public function getActiveCampaignId();

    /**
     * @param int $activeCampaignId
     */
    public function setActiveCampaignId($activeCampaignId): self;

    /**
     * @return int
     */
    public function getExportAttempts();

    /**
     * @param int $exportAttempts
     */
    public function setExportAttempts($exportAttempts): self;

    /**
     * @return string|null
     */
    public function getLastErrorCode();

    /**
     * @param string|null $lastErrorCode
     */
    public function setLastErrorCode($lastErrorCode): self;

    /**
     * @return string|null
     */
    public function getLastErrorMessage();

    /**
     * @param string|null $lastErrorMessage
     */
    public function setLastErrorMessage($lastErrorMessage): self;

    /**
     * @return string|null
     */
    public function getLastAttemptedAt();

    /**
     * @param string|null $lastAttemptedAt
     */
    public function setLastAttemptedAt($lastAttemptedAt): self;

    /**
     * @return int
     */
    public function getExportStatus();

    /**
     * @param int $exportStatus
     */
    public function setExportStatus($exportStatus): self;

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
