<?php
/**
 */

namespace CommerceLeague\ActiveCampaign\Cron;

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