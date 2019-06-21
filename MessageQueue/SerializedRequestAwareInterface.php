<?php
/**
 */

namespace CommerceLeague\ActiveCampaign\MessageQueue;

/**
 * Interface SerializedRequestAwareInterface
 */
interface SerializedRequestAwareInterface
{
    /**
     * @return string
     */
    public function getSerializedRequest(): string;

    /**
     * @param string $serializedRequest
     * @return SerializedRequestAwareInterface
     */
    public function setSerializedRequest(string $serializedRequest);

    /**
     * @return array
     */
    public function getRequest(): array;

    /**
     * @param array $request
     * @return SerializedRequestAwareInterface
     */
    public function setRequest(array $request);
}