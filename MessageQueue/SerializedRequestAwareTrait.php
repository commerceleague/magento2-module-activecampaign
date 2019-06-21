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

    /**
     * @return array
     */
    public function getRequest(): array
    {
        return json_decode($this->serializedRequest, true);
    }

    /**
     * @param array $request
     * @return SerializedRequestAwareTrait
     */
    public function setRequest(array $request)
    {
        $this->serializedRequest = json_encode($request);
        return $this;
    }
}