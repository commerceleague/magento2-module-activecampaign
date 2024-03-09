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
        return $this->_getData(self::ACTIVE_CAMPAIGN_ID);
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
        return $this->_getData(self::ENTITY_ID);
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
