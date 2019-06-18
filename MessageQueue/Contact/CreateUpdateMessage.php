<?php
declare(strict_types=1);
/**
 */
namespace CommerceLeague\ActiveCampaign\MessageQueue\Contact;

use Magento\Framework\Api\AbstractSimpleObject;

/**
 * Class CreateUpdateConsumerMessage
 */
class CreateUpdateMessage extends AbstractSimpleObject
{
    private const CONTACT_ID = 'contact_id';
    private const SERIALIZED_REQUEST = 'serialized_request';

    /**
     * @return int|null
     */
    public function getContactId(): ?int
    {
        return $this->_get(self::CONTACT_ID);
    }

    /**
     * @param int $contactId
     * @return CreateUpdateMessage
     */
    public function setContactId(int $contactId): self
    {
        return $this->setData(self::CONTACT_ID, $contactId);
    }

    /**
     * @return string|null
     */
    public function getSerializedRequest(): ?string
    {
        return $this->_get(self::SERIALIZED_REQUEST);
    }

    /**
     * @param string $serializedRequest
     * @return CreateUpdateMessage
     */
    public function setSerializedRequest(string $serializedRequest): self
    {
        return $this->setData(self::SERIALIZED_REQUEST, $serializedRequest);
    }
}
