<?php
declare(strict_types=1);
/**
 */

namespace CommerceLeague\ActiveCampaign\Model\Export;

use CommerceLeague\ActiveCampaign\Helper\Config;

/**
 * Class BackoffState
 *
 * Tracks consecutive 503 responses within a single consumer process so a 503
 * storm (ActiveCampaign rate-limiting the 10-minute cron bursts) can back off
 * instead of hammering the API. Magento DI shares a single instance of this
 * concrete class per process by default, so the counter persists across
 * consume() calls within one queue run; no etc/di.xml entry is required.
 */
class BackoffState
{
    private int $consecutive503 = 0;

    public function __construct(private readonly Config $config)
    {
    }

    public function record503(): void
    {
        $this->consecutive503++;
    }

    public function reset(): void
    {
        $this->consecutive503 = 0;
    }

    public function shouldHalt(): bool
    {
        return $this->config->isBackoffEnabled()
            && $this->consecutive503 >= $this->config->getBackoffThreshold();
    }
}
