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
    public function setId($id)
    {
        return $this->setData(self::ENTITY_ID, $id);
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
