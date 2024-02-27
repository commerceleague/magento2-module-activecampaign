<?php
/**
 */

namespace CommerceLeague\ActiveCampaign\Api;

/**
 * Interface CronInterface
 */
interface CronInterface
{
    /**
     * @return void
     */
    public function run(): void;
}