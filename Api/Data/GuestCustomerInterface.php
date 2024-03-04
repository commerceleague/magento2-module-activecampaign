<?php
declare(strict_types=1);

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

    public function getCreatedAt(): ?string;

    public function setCreatedAt(string $createdAt): GuestCustomerInterface;

    public function getUpdatedAt(): ?string;

    public function setUpdatedAt(string $updatedAt): GuestCustomerInterface;
}
