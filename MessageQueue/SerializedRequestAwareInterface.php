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
}