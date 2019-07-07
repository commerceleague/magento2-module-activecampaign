<?php
declare(strict_types=1);
/**
 */

namespace CommerceLeague\ActiveCampaign\Model;

use CommerceLeague\ActiveCampaign\Api\Data\AbandonedInterface;
use CommerceLeague\ActiveCampaign\Model\ResourceModel\Abandoned as AbandonedResource;
use Magento\Framework\Model\AbstractModel;

/**
 * Class Abandoned
 */
class Abandoned extends AbstractModel implements AbandonedInterface
{
    /**
     * @inheritDoc
     */
    protected function _construct()
    {
        $this->_init(AbandonedResource::class);
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
    public function getQuoteId()
    {
        return $this->_getData(self::QUOTE_ID);
    }

    /**
     * @inheritDoc
     */
    public function setQuoteId($quoteId): AbandonedInterface
    {
        return $this->setData(self::QUOTE_ID, $quoteId);
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
    public function setActiveCampaignId($activeCampaignId): AbandonedInterface
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
    public function setCreatedAt($createdAt): AbandonedInterface
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
    public function setUpdatedAt($updatedAt): AbandonedInterface
    {
        return $this->setData(self::UPDATED_AT, $updatedAt);
    }
}
