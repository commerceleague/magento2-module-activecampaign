<?php
declare(strict_types=1);
/**
 */
namespace CommerceLeague\ActiveCampaign\Model\ActiveCampaign;

use CommerceLeague\ActiveCampaign\Api\Data\ContactInterface;
use CommerceLeague\ActiveCampaign\Model\ResourceModel\ActiveCampaign\Contact as ContactResource;
use Magento\Framework\Model\AbstractModel;

/**
 * Class Contact
 */
class Contact extends AbstractModel implements ContactInterface
{
    /**
     * @inheritDoc
     */
    protected function _construct()
    {
        $this->_init(ContactResource::class);
    }

    /**
     * @inheritDoc
     */
    public function getId()
    {
        return $this->_getData(self::ENTITY_ID);
    }

    /**
     * @inheritDoc
     */
    public function setId($value)
    {
        return $this->setData(self::ENTITY_ID, $value);
    }

    /**
     * @inheritDoc
     */
    public function getEmail()
    {
        return $this->_getData(self::EMAIL);
    }

    /**
     * @inheritDoc
     */
    public function setEmail($email): ContactInterface
    {
        return $this->setData(self::EMAIL, $email);
    }

    /**
     * @inheritDoc
     */
    public function getActiveCampaignId()
    {
        return $this->_getData(self::ACTIVE_CAMPAIGN_ID);
    }

    /**
     * @inheritDoc
     */
    public function setActiveCampaignId($activeCampaignId): ContactInterface
    {
        return $this->setData(self::ACTIVE_CAMPAIGN_ID, $activeCampaignId);
    }

    /**
     * @inheritDoc
     */
    public function getExportAttempts()
    {
        return (int) $this->_getData(self::EXPORT_ATTEMPTS);
    }

    /**
     * @inheritDoc
     */
    public function setExportAttempts($exportAttempts): ContactInterface
    {
        return $this->setData(self::EXPORT_ATTEMPTS, $exportAttempts);
    }

    /**
     * @inheritDoc
     */
    public function getLastErrorCode()
    {
        return $this->_getData(self::LAST_ERROR_CODE);
    }

    /**
     * @inheritDoc
     */
    public function setLastErrorCode($lastErrorCode): ContactInterface
    {
        return $this->setData(self::LAST_ERROR_CODE, $lastErrorCode);
    }

    /**
     * @inheritDoc
     */
    public function getLastErrorMessage()
    {
        return $this->_getData(self::LAST_ERROR_MESSAGE);
    }

    /**
     * @inheritDoc
     */
    public function setLastErrorMessage($lastErrorMessage): ContactInterface
    {
        return $this->setData(self::LAST_ERROR_MESSAGE, $lastErrorMessage);
    }

    /**
     * @inheritDoc
     */
    public function getLastAttemptedAt()
    {
        return $this->_getData(self::LAST_ATTEMPTED_AT);
    }

    /**
     * @inheritDoc
     */
    public function setLastAttemptedAt($lastAttemptedAt): ContactInterface
    {
        return $this->setData(self::LAST_ATTEMPTED_AT, $lastAttemptedAt);
    }

    /**
     * @inheritDoc
     */
    public function getExportStatus()
    {
        return (int) $this->_getData(self::EXPORT_STATUS);
    }

    /**
     * @inheritDoc
     */
    public function setExportStatus($exportStatus): ContactInterface
    {
        return $this->setData(self::EXPORT_STATUS, $exportStatus);
    }

    /**
     * @inheritDoc
     */
    public function getCreatedAt()
    {
        return $this->_getData(self::CREATED_AT);
    }

    /**
     * @inheritDoc
     */
    public function setCreatedAt($createdAt): ContactInterface
    {
        return $this->setData(self::CREATED_AT, $createdAt);
    }

    /**
     * @inheritDoc
     */
    public function getUpdatedAt()
    {
        return $this->_getData(self::UPDATED_AT);
    }

    /**
     * @inheritDoc
     */
    public function setUpdatedAt($updatedAt): ContactInterface
    {
        return $this->setData(self::UPDATED_AT, $updatedAt);
    }
}
