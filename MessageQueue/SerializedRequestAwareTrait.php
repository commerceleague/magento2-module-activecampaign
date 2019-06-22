<?php
/**
 */

namespace CommerceLeague\ActiveCampaign\MessageQueue;

/**
 * Interface SerializedRequestAwareInterface
 */
trait SerializedRequestAwareTrait
{
    /**
     * @var string
     */
    protected $serializedRequest;

    /**
     * @return string
     */
    public function getSerializedRequest(): string
    {
        return $this->serializedRequest;
    }

    /**
     * @param string $serializedRequest
     * @return SerializedRequestAwareTrait
     */
    public function setSerializedRequest(string $serializedRequest)
    {
        $this->serializedRequest = $serializedRequest;
        return $this;
    }
}
