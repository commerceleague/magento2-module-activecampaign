<?php
declare(strict_types=1);
/**
 */

namespace CommerceLeague\ActiveCampaign\Model\ActiveCampaign;

use CommerceLeague\ActiveCampaign\Api\Data\CustomerInterface;
use CommerceLeague\ActiveCampaign\Model\ResourceModel\ActiveCampaign\Customer as CustomerResource;
use Magento\Framework\Model\AbstractModel;

/**
 * Class Customer
 */
class Customer extends AbstractModel implements CustomerInterface
{
    /**
     * @inheritDoc
     */
    protected function _construct()
    {
        $this->_init(CustomerResource::class);
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
    public function getMagentoCustomerId()
    {
        return $this->_getData(self::MAGENTO_CUSTOMER_ID);
    }

    /**
     * @inheritDoc
     */
    public function setMagentoCustomerId($magentoCustomerId): CustomerInterface
    {
        return $this->setData(self::MAGENTO_CUSTOMER_ID, $magentoCustomerId);
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
    public function setActiveCampaignId($activeCampaignId): CustomerInterface
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
    public function setExportAttempts($exportAttempts): CustomerInterface
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
    public function setLastErrorCode($lastErrorCode): CustomerInterface
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
    public function setLastErrorMessage($lastErrorMessage): CustomerInterface
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
    public function setLastAttemptedAt($lastAttemptedAt): CustomerInterface
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
    public function setExportStatus($exportStatus): CustomerInterface
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
    public function setCreatedAt($createdAt): CustomerInterface
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
    public function setUpdatedAt($updatedAt): CustomerInterface
    {
        return $this->setData(self::UPDATED_AT, $updatedAt);
    }
}
