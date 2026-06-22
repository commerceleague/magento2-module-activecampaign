<?php
declare(strict_types=1);
/**
 */

namespace CommerceLeague\ActiveCampaign\Model\ActiveCampaign;

use CommerceLeague\ActiveCampaign\Api\Data\OrderInterface;
use CommerceLeague\ActiveCampaign\Model\ResourceModel\ActiveCampaign\Order as OrderResource;
use Magento\Framework\Model\AbstractModel;

/**
 * Class Order
 */
class Order extends AbstractModel implements OrderInterface
{
    /**
     * @inheritDoc
     */
    protected function _construct()
    {
        $this->_init(OrderResource::class);
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
    public function getMagentoQuoteId()
    {
        return $this->_getData(self::MAGENTO_QUOTE_ID);
    }

    /**
     * @inheritDoc
     */
    public function setMagentoQuoteId($magentoQuoteId): OrderInterface
    {
        return $this->setData(self::MAGENTO_QUOTE_ID, $magentoQuoteId);
    }

    /**
     * @inheritDoc
     */
    public function getMagentoOrderId()
    {
        return $this->_getData(self::MAGENTO_ORDER_ID);
    }

    /**
     * @inheritDoc
     */
    public function setMagentoOrderId($magentoOrderId): OrderInterface
    {
        return $this->setData(self::MAGENTO_ORDER_ID, $magentoOrderId);
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
    public function setActiveCampaignId($activeCampaignId): OrderInterface
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
    public function setExportAttempts($exportAttempts): OrderInterface
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
    public function setLastErrorCode($lastErrorCode): OrderInterface
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
    public function setLastErrorMessage($lastErrorMessage): OrderInterface
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
    public function setLastAttemptedAt($lastAttemptedAt): OrderInterface
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
    public function setExportStatus($exportStatus): OrderInterface
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
    public function setCreatedAt($createdAt): OrderInterface
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
    public function setUpdatedAt($updatedAt): OrderInterface
    {
        return $this->setData(self::UPDATED_AT, $updatedAt);
    }
}
