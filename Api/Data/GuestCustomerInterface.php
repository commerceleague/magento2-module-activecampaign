<?php
declare(strict_types=1);

namespace CommerceLeague\ActiveCampaign\Api\Data;

/**
 * Interface GuestCustomerInterface
 */
interface GuestCustomerInterface extends FailureTrackableInterface
{

    public const ENTITY_ID          = 'entity_id';
    public const ACTIVE_CAMPAIGN_ID = 'activecampaign_id';
    public const EMAIL              = 'email';
    public const FIRSTNAME          = 'firstname';
    public const LASTNAME           = 'lastname';
    public const EXPORT_ATTEMPTS    = 'export_attempts';
    public const LAST_ERROR_CODE    = 'last_error_code';
    public const LAST_ERROR_MESSAGE = 'last_error_message';
    public const LAST_ATTEMPTED_AT  = 'last_attempted_at';
    public const EXPORT_STATUS      = 'export_status';
    public const CREATED_AT         = 'created_at';
    public const UPDATED_AT         = 'updated_at';

    public const EXPORT_STATUS_PENDING = 0;
    public const EXPORT_STATUS_SYNCED  = 1;
    public const EXPORT_STATUS_FAILED  = 2;

    public function getId(): ?int;

    /**
     * @param int|mixed $value
     */
    public function setId(mixed $value): GuestCustomerInterface;

    public function getActiveCampaignId(): ?int;

    public function setActiveCampaignId(int $activeCampaignId): GuestCustomerInterface;

    public function getEmail(): ?string;

    public function setEmail(string $email): GuestCustomerInterface;

    public function getFirstname(): ?string;

    public function setFirstname(string $firstname): GuestCustomerInterface;

    public function getLastname(): ?string;

    public function setLastname(string $lastname): GuestCustomerInterface;

    public function getExportAttempts(): int;

    public function setExportAttempts(int $exportAttempts): GuestCustomerInterface;

    public function getLastErrorCode(): ?string;

    public function setLastErrorCode(?string $lastErrorCode): GuestCustomerInterface;

    public function getLastErrorMessage(): ?string;

    public function setLastErrorMessage(?string $lastErrorMessage): GuestCustomerInterface;

    public function getLastAttemptedAt(): ?string;

    public function setLastAttemptedAt(?string $lastAttemptedAt): GuestCustomerInterface;

    public function getExportStatus(): int;

    public function setExportStatus(int $exportStatus): GuestCustomerInterface;

    public function getCreatedAt(): ?string;

    public function setCreatedAt(string $createdAt): GuestCustomerInterface;

    public function getUpdatedAt(): ?string;

    public function setUpdatedAt(string $updatedAt): GuestCustomerInterface;
}
