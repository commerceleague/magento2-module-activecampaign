<?php
declare(strict_types=1);
/**
 */
namespace CommerceLeague\ActiveCampaign\MessageQueue\Contact;

use Magento\Framework\Api\AbstractSimpleObject;

/**
 * Class RemoveMessage
 */
class RemoveMessage extends AbstractSimpleObject
{
    public const ACTIVE_CAMPAIGN_ID = 'active_campaign_id';

    /**
     * @return int|null
     */
    public function getActiveCampaignId()
    {
        return $this->_get(self::ACTIVE_CAMPAIGN_ID);
    }

    /**
     * @param int $activeCampaignId
     * @return RemoveMessage
     */
    public function setActiveCampaignId($activeCampaignId): self
    {
        return $this->setData(self::ACTIVE_CAMPAIGN_ID, $activeCampaignId);
    }
}
