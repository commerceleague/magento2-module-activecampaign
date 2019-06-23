<?php
declare(strict_types=1);
/**
 */
namespace CommerceLeague\ActiveCampaign\Model;

use CommerceLeague\ActiveCampaign\Api\Data\ContactInterface;
use CommerceLeague\ActiveCampaign\Model\ResourceModel\Contact as ContactResource;
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
        return $this->_getData(self::CONTACT_ID);
    }

    /**
     * @inheritDoc
     */
    public function setId($id)
    {
        return $this->setData(self::CONTACT_ID, $id);
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
}
