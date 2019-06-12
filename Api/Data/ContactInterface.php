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
    public const EXTERNAL_ID = 'external_id';
    public const EMAIL = 'email';

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
    public function getExternalId();

    /**
     * @param int $externalId
     * @return ContactInterface
     */
    public function setExternalId($externalId): self;

    /**
     * @return string|null
     */
    public function getEmail();

    /**
     * @param string $email
     * @return ContactInterface
     */
    public function setEmail($email): self;
}
