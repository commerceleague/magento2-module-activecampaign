<?php
declare(strict_types=1);
/**
 */

namespace CommerceLeague\ActiveCampaign\Model;

use CommerceLeague\ActiveCampaign\Api\Data\ConnectionInterface;
use CommerceLeague\ActiveCampaign\Model\ResourceModel\Connection as ConnectionResource;
use Magento\Framework\Model\AbstractModel;

class Connection extends AbstractModel implements ConnectionInterface
{
    /**
     * @inheritDoc
     */
    protected function _construct()
    {
        $this->_init(ConnectionResource::class);
    }

    /**
     * @inheritDoc
     */
    public function getId()
    {
        return $this->_getData(self::CONNECTION_ID);
    }

    /**
     * @inheritDoc
     */
    public function setId($id)
    {
        return $this->setData(self::CONNECTION_ID, $id);
    }

    /**
     * @inheritDoc
     */
    public function getName()
    {
        return $this->_getData(self::NAME);
    }

    /**
     * @inheritDoc
     */
    public function setName($name): ConnectionInterface
    {
        return $this->setData(self::NAME, $name);
    }

    /**
     * @inheritDoc
     */
    public function getLogoUrl()
    {
        return $this->getData(self::LOGO_URL);
    }

    /**
     * @inheritDoc
     */
    public function setLogoUrl($logoUrl): ConnectionInterface
    {
        return $this->setData(self::LOGO_URL, $logoUrl);
    }

    /**
     * @inheritDoc
     */
    public function getLinkUrl()
    {
        return $this->_getData(self::LINK_URL);
    }

    /**
     * @inheritDoc
     */
    public function setLinkUrl($linkUrl): ConnectionInterface
    {
        return $this->setData(self::LINK_URL, $linkUrl);
    }

    /**
     * @inheritDoc
     */
    public function getActiveCampaignId()
    {
        return $this->getData(self::ACTIVE_CAMPAIGN_ID);
    }

    /**
     * @inheritDoc
     */
    public function setActiveCampaignId($activeCampaignId): ConnectionInterface
    {
        return $this->setData(self::ACTIVE_CAMPAIGN_ID, $activeCampaignId);
    }

    /**
     * @inheritDoc
     */
    public function getCreatedAt()
    {
        return $this->getData(self::CREATED_AT);
    }

    /**
     * @inheritDoc
     */
    public function setCreatedAt($createdAt): ConnectionInterface
    {
        return $this->setData(self::CREATED_AT);
    }

    /**
     * @inheritDoc
     */
    public function getUpdatedAt()
    {
        return $this->getData(self::UPDATED_AT);
    }

    /**
     * @inheritDoc
     */
    public function setUpdatedAt($updatedAt): ConnectionInterface
    {
        return $this->setData(self::UPDATED_AT, $updatedAt);
    }
}
