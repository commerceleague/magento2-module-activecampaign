<?php
declare(strict_types=1);
/**
 */
namespace CommerceLeague\ActiveCampaign\MessageQueue\Contact;

use CommerceLeague\ActiveCampaign\MessageQueue\SerializedRequestAwareInterface;
use CommerceLeague\ActiveCampaign\MessageQueue\SerializedRequestAwareTrait;

/**
 * Class CreateUpdateConsumerMessage
 */
class CreateUpdateMessage implements SerializedRequestAwareInterface
{
    use SerializedRequestAwareTrait;

    /**
     * @var int
     */
    private $contactId;

    /**
     * @return int
     */
    public function getContactId(): int
    {
        return $this->contactId;
    }

    /**
     * @param int $contactId
     * @return $this
     */
    public function setContactId(int $contactId): self
    {
        $this->contactId = $contactId;
        return $this;
    }

    /**
     * @param int $contactId
     * @param array $request
     * @return CreateUpdateMessage
     */
    public static function build(int $contactId, array $request): self
    {
        $message = new self();
        $message->setContactId($contactId);
        $message->setSerializedRequest(json_encode($request));

        return $message;
    }
}
