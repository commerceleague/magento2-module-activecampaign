<?php
declare(strict_types=1);
/**
 */

namespace CommerceLeague\ActiveCampaign\MessageQueue;

/**
 * Interface ConsumerInterface
 */
interface ConsumerInterface
{
    public function consume(string $message): void;
}
