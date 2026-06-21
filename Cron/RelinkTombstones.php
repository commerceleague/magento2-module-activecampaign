<?php
declare(strict_types=1);
/**
 * Copyright © André Flitsch. All rights reserved.
 * See license.md for license details.
 */

namespace CommerceLeague\ActiveCampaign\Cron;

use CommerceLeague\ActiveCampaign\Api\CronInterface;
use CommerceLeague\ActiveCampaign\Helper\Config as ConfigHelper;
use CommerceLeague\ActiveCampaign\Model\Tombstone\TombstoneReconciler;
use CommerceLeague\ActiveCampaign\Model\Tombstone\TombstoneRelinker;
use Psr\Log\LoggerInterface;

/**
 * Class RelinkTombstones
 *
 * Config-gated weekly cron that runs the bulk, id-preserving tombstone relink
 * (the scheduled equivalent of `activecampaign:relink:tombstones --commit`).
 *
 * This is the only path that keeps GUEST tombstones reconciled: the guest export
 * consumer only runs when a guest has no AC id, but a tombstone holds the dead id
 * so it is never self-healed there. Disabled by default — enable it (together
 * with tombstone_selfheal_enabled) only after running the bulk relink manually
 * once (--dry-run, operator-reviewed, then --commit). Idempotent and safe to run
 * repeatedly: the relinker skips non-tombstones, no-live-contact and conflicts.
 */
class RelinkTombstones implements CronInterface
{
    public function __construct(
        private readonly ConfigHelper $configHelper,
        private readonly TombstoneReconciler $reconciler,
        private readonly LoggerInterface $logger
    ) {
    }

    /**
     * @inheritDoc
     */
    public function run(): void
    {
        if (!$this->configHelper->isRelinkCronEnabled()) {
            return;
        }

        $tally = $this->reconciler->reconcile(
            true,
            function (array $record): void {
                $this->logger->info(sprintf(
                    'ActiveCampaign relink cron: ecomId=%d type=%s email=%s -> %s',
                    (int)($record['ecomCustomerId'] ?? 0),
                    (string)($record['type'] ?? ''),
                    (string)($record['realEmail'] ?? ''),
                    (string)($record['outcome'] ?? '')
                ));
            }
        );

        $this->logger->info(sprintf(
            'ActiveCampaign relink cron summary: relinked=%d skipped_not_tombstone=%d '
            . 'skipped_conflict=%d no_live_contact=%d not_found=%d unresolved=%d error=%d',
            (int)($tally[TombstoneRelinker::RESULT_RELINKED] ?? 0),
            (int)($tally[TombstoneRelinker::RESULT_SKIPPED_NOT_TOMBSTONE] ?? 0),
            (int)($tally[TombstoneRelinker::RESULT_SKIPPED_CONFLICT] ?? 0),
            (int)($tally[TombstoneRelinker::RESULT_NO_LIVE_CONTACT] ?? 0),
            (int)($tally[TombstoneRelinker::RESULT_NOT_FOUND] ?? 0),
            (int)($tally[TombstoneReconciler::KEY_UNRESOLVED] ?? 0),
            (int)($tally[TombstoneReconciler::KEY_ERROR] ?? 0)
        ));
    }
}
