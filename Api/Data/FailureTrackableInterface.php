<?php
declare(strict_types=1);
/**
 */

namespace CommerceLeague\ActiveCampaign\Api\Data;

/**
 * Interface FailureTrackableInterface
 *
 * Marker contract for mapping entities that track export failure metadata.
 * Declares the loosest signatures compatible with both the untyped accessor
 * style (Order/Contact/Customer) and the strict typed style (GuestCustomer),
 * so all four data interfaces may extend it without breaking LSP.
 */
interface FailureTrackableInterface
{
    public const EXPORT_STATUS_PENDING = 0;
    public const EXPORT_STATUS_SYNCED  = 1;
    public const EXPORT_STATUS_FAILED  = 2;

    /**
     * @return int
     */
    public function getExportAttempts();

    /**
     * @param int $exportAttempts
     */
    public function setExportAttempts(int $exportAttempts);

    /**
     * @return string|null
     */
    public function getLastErrorCode();

    /**
     * @param string|null $lastErrorCode
     */
    public function setLastErrorCode(?string $lastErrorCode);

    /**
     * @return string|null
     */
    public function getLastErrorMessage();

    /**
     * @param string|null $lastErrorMessage
     */
    public function setLastErrorMessage(?string $lastErrorMessage);

    /**
     * @return string|null
     */
    public function getLastAttemptedAt();

    /**
     * @param string|null $lastAttemptedAt
     */
    public function setLastAttemptedAt(?string $lastAttemptedAt);

    /**
     * @return int
     */
    public function getExportStatus();

    /**
     * @param int $exportStatus
     */
    public function setExportStatus(int $exportStatus);
}
