<?php
declare(strict_types=1);
/**
 */

namespace CommerceLeague\ActiveCampaign\Model\ActiveCampaign;

use CommerceLeague\ActiveCampaign\Api\Data\GuestCustomerInterface;
use CommerceLeague\ActiveCampaign\Model\ResourceModel\ActiveCampaign\GuestCustomer as GuestCustomerResource;
use Magento\Framework\Model\AbstractModel;

/**
 * Class GuestCustomer
 */
class GuestCustomer extends AbstractModel implements GuestCustomerInterface
{

    /**
     * @inheritDoc
     */
    public function getActiveCampaignId(): ?int
    {
        $value = $this->_getData(self::ACTIVE_CAMPAIGN_ID);
        return $value !== null ? (int) $value : null;
    }

    /**
     * @inheritDoc
     */
    public function getCreatedAt(): ?string
    {
        return $this->_getData(self::CREATED_AT);
    }

    /**
     * @return string|null
     */
    public function getEmail(): ?string
    {
        return $this->_getData(self::EMAIL);
    }

    /**
     * @return string|null
     */
    public function getFirstname(): ?string
    {
        return $this->_getData(self::FIRSTNAME);
    }

    /**
     * @inheritDoc
     */
    public function getId(): ?int
    {
        $value = $this->_getData(self::ENTITY_ID);
        return $value !== null ? (int) $value : null;
    }

    /**
     * @return string|null
     */
    public function getLastname(): ?string
    {
        return $this->_getData(self::LASTNAME);
    }

    /**
     * @inheritDoc
     */
    public function getExportAttempts(): int
    {
        return (int) $this->_getData(self::EXPORT_ATTEMPTS);
    }

    /**
     * @inheritDoc
     */
    public function getLastErrorCode(): ?string
    {
        return $this->_getData(self::LAST_ERROR_CODE);
    }

    /**
     * @inheritDoc
     */
    public function getLastErrorMessage(): ?string
    {
        return $this->_getData(self::LAST_ERROR_MESSAGE);
    }

    /**
     * @inheritDoc
     */
    public function getLastAttemptedAt(): ?string
    {
        return $this->_getData(self::LAST_ATTEMPTED_AT);
    }

    /**
     * @inheritDoc
     */
    public function getExportStatus(): int
    {
        return (int) $this->_getData(self::EXPORT_STATUS);
    }

    /**
     * @inheritDoc
     */
    public function getUpdatedAt(): ?string
    {
        return $this->_getData(self::UPDATED_AT);
    }

    /**
     * @inheritDoc
     */
    public function setActiveCampaignId(int $activeCampaignId): GuestCustomerInterface
    {
        return $this->setData(self::ACTIVE_CAMPAIGN_ID, $activeCampaignId);
    }

    /**
     * @inheritDoc
     */
    public function setCreatedAt(string $createdAt): GuestCustomerInterface
    {
        return $this->setData(self::CREATED_AT, $createdAt);
    }

    /**
     * @param string $email
     *
     * @return GuestCustomerInterface
     */
    public function setEmail(string $email): GuestCustomerInterface
    {
        return $this->setData(self::EMAIL, $email);
    }

    /**
     * @param string $firstname
     *
     * @return GuestCustomerInterface
     */
    public function setFirstname(string $firstname): GuestCustomerInterface
    {
        return $this->setData(self::FIRSTNAME, $firstname);
    }

    /**
     * @inheritDoc
     */
    public function setId(mixed $value): GuestCustomerInterface
    {
        return $this->setData(self::ENTITY_ID, $value);
    }

    /**
     * @param string $lastname
     *
     * @return GuestCustomerInterface
     */
    public function setLastname(string $lastname): GuestCustomerInterface
    {
        return $this->setData(self::LASTNAME, $lastname);
    }

    /**
     * @inheritDoc
     */
    public function setExportAttempts(int $exportAttempts): GuestCustomerInterface
    {
        return $this->setData(self::EXPORT_ATTEMPTS, $exportAttempts);
    }

    /**
     * @inheritDoc
     */
    public function setLastErrorCode(?string $lastErrorCode): GuestCustomerInterface
    {
        return $this->setData(self::LAST_ERROR_CODE, $lastErrorCode);
    }

    /**
     * @inheritDoc
     */
    public function setLastErrorMessage(?string $lastErrorMessage): GuestCustomerInterface
    {
        return $this->setData(self::LAST_ERROR_MESSAGE, $lastErrorMessage);
    }

    /**
     * @inheritDoc
     */
    public function setLastAttemptedAt(?string $lastAttemptedAt): GuestCustomerInterface
    {
        return $this->setData(self::LAST_ATTEMPTED_AT, $lastAttemptedAt);
    }

    /**
     * @inheritDoc
     */
    public function setExportStatus(int $exportStatus): GuestCustomerInterface
    {
        return $this->setData(self::EXPORT_STATUS, $exportStatus);
    }

    /**
     * @inheritDoc
     */
    public function setUpdatedAt(string $updatedAt): GuestCustomerInterface
    {
        return $this->setData(self::UPDATED_AT, $updatedAt);
    }

    /**
     * @inheritDoc
     */
    protected function _construct()
    {
        $this->_init(GuestCustomerResource::class);
    }
}
