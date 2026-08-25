<?php
declare(strict_types=1);
/**
 * Copyright © André Flitsch. All rights reserved.
 * See license.md for license details.
 */

namespace CommerceLeague\ActiveCampaign\Model\Tombstone;

use CommerceLeague\ActiveCampaign\Gateway\Client;
use CommerceLeague\ActiveCampaign\Model\ActiveCampaign\Contact;
use CommerceLeague\ActiveCampaign\Model\ResourceModel\ActiveCampaign\Contact\CollectionFactory as ContactCollectionFactory;
use CommerceLeague\ActiveCampaignApi\Exception\HttpException;
use CommerceLeague\ActiveCampaignApi\Exception\NotFoundHttpException;

/**
 * Class ContactTombstoneChecker
 *
 * Read-only audit of the activecampaign_contact mapping: for every row marked
 * synced (has a non-null activecampaign_id), confirms the Contact still exists
 * in AC via a GET. Unlike a Customer/GuestCustomer tombstone (see
 * {@see TombstoneRelinker}), AC does not anonymize a deleted Contact and keep
 * it under the same id — deleting a Contact removes it outright, so the only
 * possible outcomes here are "still exists" or "gone" (404). There is nothing
 * to relink; a gone contact can only be recreated via a fresh export.
 *
 * Performs exactly one GET per candidate row, so callers should bound the run
 * with $email or $limit on any AC account with a large contact table.
 */
class ContactTombstoneChecker
{
    private const FIELD_ACTIVE_CAMPAIGN_ID = 'activecampaign_id';
    private const FIELD_EMAIL              = 'email';

    public const RESULT_FOUND     = 'found';
    public const RESULT_NOT_FOUND = 'not_found';
    public const RESULT_ERROR     = 'error';

    /**
     * Paces requests to roughly AC's documented budget (see the spam-detector
     * cleanup tooling's RATE_LIMIT_SLEEP) so a full-table run doesn't trigger
     * throttling in the first place.
     */
    private const REQUEST_PACING_MICROSECONDS = 100_000;

    private const MAX_TRANSIENT_RETRIES  = 3;
    private const BASE_BACKOFF_MICROSECONDS = 1_000_000;

    public function __construct(
        private readonly ContactCollectionFactory $contactCollectionFactory,
        private readonly Client $client
    ) {
    }

    /**
     * @param string|null $email exact-match filter; null checks every synced contact
     * @param int $limit cap on rows checked; 0 = no cap
     * @param callable|null $onRecord optional per-row callback receiving
     *        array{entityId:int, email:string, activeCampaignId:int, outcome:string, error?:string}
     * @return array<string, int> tally keyed by the RESULT_* constants
     */
    public function check(?string $email, int $limit, ?callable $onRecord = null): array
    {
        $tally = [
            self::RESULT_FOUND     => 0,
            self::RESULT_NOT_FOUND => 0,
            self::RESULT_ERROR     => 0,
        ];

        foreach ($this->collectCandidates($email, $limit) as $contact) {
            $activeCampaignId = (int)$contact->getActiveCampaignId();
            $outcome          = $this->checkOne($activeCampaignId);
            $tally[$outcome[0]]++;

            if ($onRecord !== null) {
                $record = [
                    'entityId'         => (int)$contact->getId(),
                    'email'            => (string)$contact->getEmail(),
                    'activeCampaignId' => $activeCampaignId,
                    'outcome'          => $outcome[0],
                ];

                if ($outcome[1] !== null) {
                    $record['error'] = $outcome[1];
                }

                $onRecord($record);
            }
        }

        return $tally;
    }

    /**
     * @return array{0:string,1:?string} the RESULT_* outcome and, for RESULT_ERROR, the exception message
     */
    private function checkOne(int $activeCampaignId): array
    {
        for ($attempt = 0; $attempt <= self::MAX_TRANSIENT_RETRIES; $attempt++) {
            $this->sleep(self::REQUEST_PACING_MICROSECONDS);

            try {
                $this->client->getContactApi()->get($activeCampaignId);

                return [self::RESULT_FOUND, null];
            } catch (NotFoundHttpException) {
                return [self::RESULT_NOT_FOUND, null];
            } catch (HttpException $exception) {
                // Same transient classification as the export consumers
                // ($code >= 500 or 429): back off and retry rather than
                // mistaking a rate-limit hit for a confirmed permanent error.
                $transient = $exception->getCode() >= 500 || $exception->getCode() === 429;

                if (!$transient || $attempt === self::MAX_TRANSIENT_RETRIES) {
                    return [self::RESULT_ERROR, $exception->getMessage()];
                }

                $this->sleep(self::BASE_BACKOFF_MICROSECONDS * (2 ** $attempt));
            } catch (\Throwable $exception) {
                return [self::RESULT_ERROR, $exception->getMessage()];
            }
        }

        // Unreachable: the last loop iteration always hits a return above.
        return [self::RESULT_ERROR, 'exhausted retries'];
    }

    /**
     * Extracted so tests can stub out real sleeping.
     */
    protected function sleep(int $microseconds): void
    {
        usleep($microseconds);
    }

    /**
     * @return array<int, Contact>
     */
    private function collectCandidates(?string $email, int $limit): array
    {
        $collection = $this->contactCollectionFactory->create();
        $collection->addFieldToFilter(self::FIELD_ACTIVE_CAMPAIGN_ID, ['notnull' => true]);

        if ($email !== null) {
            $collection->addFieldToFilter(self::FIELD_EMAIL, $email);
        }

        if ($limit > 0) {
            // A bare LIMIT would always take the oldest rows first (lowest
            // entity_id) — not a representative slice of the table. Order
            // randomly so a bounded run gives a real estimate of scope.
            $collection->getSelect()->orderRand();
            $collection->setPageSize($limit);
        }

        /** @var array<int, Contact> $items */
        $items = $collection->getItems();

        return $items;
    }
}
