<?php
declare(strict_types=1);
/**
 */
namespace CommerceLeague\ActiveCampaign\MessageQueue\Contact;

/**
 * Class CreateUpdateConsumerMessage
 */
class CreateUpdateMessage
{
    /**
     * @var int
     */
    private $contactId;

    /**
     * @var string
     */
    private $serializedRequest;

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
     * @return string
     */
    public function getSerializedRequest(): string
    {
        return $this->serializedRequest;
    }

    /**
     * @param string $serializedRequest
     * @return $this
     */
    public function setSerializedRequest(string $serializedRequest): self
    {
        $this->serializedRequest = $serializedRequest;
        return $this;
    }

    /**
     * @param int $contactId
     * @param string $serializedRequest
     * @return CreateUpdateMessage
     */
    public static function build(int $contactId, string $serializedRequest): self
    {
        $message = new self();
        $message->contactId = $contactId;
        $message->serializedRequest = $serializedRequest;

        return $message;
    }
}
