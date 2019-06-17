<?php
declare(strict_types=1);
/**
 */
namespace CommerceLeague\ActiveCampaign\MessageQueue\Contact;

use Magento\Framework\Api\AbstractSimpleObject;

/**
 * Class CreateUpdateMessage
 */
class CreateUpdateMessage extends AbstractSimpleObject
{
    public const CUSTOMER_ID = 'customer_id';

    /**
     * @return int|null
     */
    public function getCustomerId()
    {
        return $this->_get(self::CUSTOMER_ID);
    }

    /**
     * @param int $customerId
     * @return CreateUpdateMessage
     */
    public function setCustomerId($customerId): self
    {
        return $this->setData(self::CUSTOMER_ID, $customerId);
    }
}
