<?php
/**
 */

namespace CommerceLeague\ActiveCampaign\Api;

/**
 * Interface CronInterface
 */
interface CronInterface
{
    public function run(): void;
}