<?php
declare(strict_types=1);

namespace CommerceLeague\ActiveCampaign\Api\Data;

use Magento\Tests\NamingConvention\true\string;

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
    public function getId(): ?int;

    /**
     * @param int|mixed $value
     *
     * @return GuestCustomerInterface
     */
    public function setId(mixed $value): GuestCustomerInterface;

    /**
     * @return int|null
     */
    public function getActiveCampaignId(): ?int;

    /**
     * @param int $activeCampaignId
     *
     * @return GuestCustomerInterface
     */
    public function setActiveCampaignId(int $activeCampaignId): GuestCustomerInterface;

    /**
     * @return string|null
     */
    public function getEmail(): ?string;

    /**
     * @param string $email
     *
     * @return GuestCustomerInterface
     */
    public function setEmail(string $email): GuestCustomerInterface;

    /**
     * @return string|null
     */
    public function getFirstname(): ?string;

    /**
     * @param string $firstname
     *
     * @return GuestCustomerInterface
     */
    public function setFirstname(string $firstname): GuestCustomerInterface;

    /**
     * @return string|null
     */
    public function getLastname(): ?string;

    /**
     * @param string $lastname
     *
     * @return GuestCustomerInterface
     */
    public function setLastname(string $lastname): GuestCustomerInterface;

    /**
     * @return string|null
     */
    public function getCreatedAt(): ?string;

    /**
     * @param string $createdAt
     *
     * @return GuestCustomerInterface
     */
    public function setCreatedAt(string $createdAt): GuestCustomerInterface;

    /**
     * @return string|null
     */
    public function getUpdatedAt(): ?string;

    /**
     * @param string $updatedAt
     *
     * @return GuestCustomerInterface
     */
    public function setUpdatedAt(string $updatedAt): GuestCustomerInterface;
}
