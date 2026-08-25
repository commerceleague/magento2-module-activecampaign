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
        try {
            $this->client->getContactApi()->get($activeCampaignId);

            return [self::RESULT_FOUND, null];
        } catch (NotFoundHttpException) {
            return [self::RESULT_NOT_FOUND, null];
        } catch (\Throwable $exception) {
            return [self::RESULT_ERROR, $exception->getMessage()];
        }
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
            $collection->setPageSize($limit);
        }

        /** @var array<int, Contact> $items */
        $items = $collection->getItems();

        return $items;
    }
}
