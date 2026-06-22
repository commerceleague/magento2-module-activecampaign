<?php
declare(strict_types=1);
/**
 * Copyright © André Flitsch. All rights reserved.
 * See license.md for license details.
 */

namespace CommerceLeague\ActiveCampaign\Model\Tombstone;

use CommerceLeague\ActiveCampaign\Model\ActiveCampaign\Customer;
use CommerceLeague\ActiveCampaign\Model\ActiveCampaign\GuestCustomer;
use CommerceLeague\ActiveCampaign\Model\ResourceModel\ActiveCampaign\Customer\CollectionFactory as CustomerCollectionFactory;
use CommerceLeague\ActiveCampaign\Model\ResourceModel\ActiveCampaign\GuestCustomer\CollectionFactory as GuestCustomerCollectionFactory;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Framework\Exception\LocalizedException;

/**
 * Class TombstoneReconciler
 *
 * Holds the batch reconciliation of tombstoned ecomCustomers: it collects every
 * registered + guest mapping that has a non-null activecampaign_id, resolves each
 * to (ecomCustomerId, realEmail, externalId, type) and delegates the actual
 * id-preserving relink decision to the idempotent {@see TombstoneRelinker}.
 *
 * Each per-candidate relink is isolated in a try/catch (\Throwable) so a single
 * transient failure (e.g. a 503 from the relinker's GET) is counted as an error
 * and the run continues, respecting AC rate limits (one GET per candidate). The
 * resolution rules are shared with RelinkTombstonesCommand to avoid duplication.
 */
class TombstoneReconciler
{
    private const FIELD_ACTIVE_CAMPAIGN_ID = 'activecampaign_id';

    public const TYPE_REGISTERED = 'registered';
    public const TYPE_GUEST      = 'guest';

    public const KEY_UNRESOLVED = 'unresolved';
    public const KEY_ERROR      = 'error';

    public function __construct(
        private readonly CustomerCollectionFactory $customerCollectionFactory,
        private readonly GuestCustomerCollectionFactory $guestCustomerCollectionFactory,
        private readonly CustomerRepositoryInterface $customerRepository,
        private readonly TombstoneRelinker $relinker
    ) {
    }

    /**
     * Reconcile all registered + guest tombstone candidates.
     *
     * @param bool $commit pass-through to the relinker; false = dry-run.
     * @param callable|null $onRecord optional per-candidate callback receiving an
     *        array{ecomCustomerId:int, realEmail:string, externalId:string,
     *        type:string, outcome:string} (outcome is a RESULT_* constant or one
     *        of the KEY_* sentinels for unresolved/error).
     * @return array<string, int> tally keyed by the RESULT_* constants plus
     *         KEY_UNRESOLVED and KEY_ERROR.
     */
    public function reconcile(bool $commit, ?callable $onRecord = null): array
    {
        $tally = [
            TombstoneRelinker::RESULT_RELINKED              => 0,
            TombstoneRelinker::RESULT_SKIPPED_NOT_TOMBSTONE => 0,
            TombstoneRelinker::RESULT_SKIPPED_CONFLICT      => 0,
            TombstoneRelinker::RESULT_NO_LIVE_CONTACT       => 0,
            TombstoneRelinker::RESULT_NOT_FOUND             => 0,
            self::KEY_UNRESOLVED                            => 0,
            self::KEY_ERROR                                 => 0,
        ];

        foreach ($this->collectCandidates() as $candidate) {
            $resolved = $this->resolveCandidate($candidate);

            if ($resolved === null) {
                $tally[self::KEY_UNRESOLVED]++;
                if ($onRecord !== null) {
                    [$type, $model] = $candidate;
                    $onRecord([
                        'ecomCustomerId' => 0,
                        'realEmail'      => '',
                        'externalId'     => '',
                        'type'           => $type,
                        'outcome'        => self::KEY_UNRESOLVED,
                    ]);
                }
                continue;
            }

            [$ecomCustomerId, $realEmail, $externalId, $type] = $resolved;

            try {
                $outcome = $this->relinker->relink($ecomCustomerId, $realEmail, $externalId, $commit);
            } catch (\Throwable $exception) {
                $tally[self::KEY_ERROR]++;
                if ($onRecord !== null) {
                    $onRecord([
                        'ecomCustomerId' => $ecomCustomerId,
                        'realEmail'      => $realEmail,
                        'externalId'     => $externalId,
                        'type'           => $type,
                        'outcome'        => self::KEY_ERROR,
                        'error'          => $exception->getMessage(),
                    ]);
                }
                continue;
            }

            $tally[$outcome] = ($tally[$outcome] ?? 0) + 1;

            if ($onRecord !== null) {
                $onRecord([
                    'ecomCustomerId' => $ecomCustomerId,
                    'realEmail'      => $realEmail,
                    'externalId'     => $externalId,
                    'type'           => $type,
                    'outcome'        => $outcome,
                ]);
            }
        }

        return $tally;
    }

    /**
     * Collect every registered + guest mapping with a non-null activecampaign_id.
     *
     * @return array<int, array{0:string,1:\Magento\Framework\Model\AbstractModel}>
     */
    private function collectCandidates(): array
    {
        $candidates = [];

        $customerCollection = $this->customerCollectionFactory->create();
        $customerCollection->addFieldToFilter(self::FIELD_ACTIVE_CAMPAIGN_ID, ['notnull' => true]);

        /** @var Customer $model */
        foreach ($customerCollection->getItems() as $model) {
            $candidates[] = [self::TYPE_REGISTERED, $model];
        }

        $guestCollection = $this->guestCustomerCollectionFactory->create();
        $guestCollection->addFieldToFilter(self::FIELD_ACTIVE_CAMPAIGN_ID, ['notnull' => true]);

        /** @var GuestCustomer $model */
        foreach ($guestCollection->getItems() as $model) {
            $candidates[] = [self::TYPE_GUEST, $model];
        }

        return $candidates;
    }

    /**
     * Resolve a raw mapping into (ecomCustomerId, realEmail, externalId, type).
     *
     * Returns null when the data cannot be resolved (e.g. a registered mapping
     * whose Magento customer record was itself deleted).
     *
     * @param array{0:string,1:\Magento\Framework\Model\AbstractModel} $candidate
     * @return array{0:int,1:string,2:string,3:string}|null
     */
    public function resolveCandidate(array $candidate): ?array
    {
        [$type, $model] = $candidate;

        if ($type === self::TYPE_GUEST) {
            /** @var GuestCustomer $model */
            $ecomCustomerId = (int)$model->getActiveCampaignId();
            $realEmail      = (string)$model->getEmail();
            $externalId     = 'guest-' . (int)$model->getId();

            return [$ecomCustomerId, $realEmail, $externalId, self::TYPE_GUEST];
        }

        /** @var Customer $model */
        $magentoCustomerId = (int)$model->getMagentoCustomerId();

        try {
            $realEmail = (string)$this->customerRepository->getById($magentoCustomerId)->getEmail();
        } catch (LocalizedException) {
            return null;
        }

        $ecomCustomerId = (int)$model->getActiveCampaignId();

        return [$ecomCustomerId, $realEmail, (string)$magentoCustomerId, self::TYPE_REGISTERED];
    }
}
