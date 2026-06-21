<?php
declare(strict_types=1);
/**
 * Copyright © André Flitsch. All rights reserved.
 * See license.md for license details.
 */

namespace CommerceLeague\ActiveCampaign\Model\Tombstone;

use CommerceLeague\ActiveCampaign\Gateway\Client;
use CommerceLeague\ActiveCampaign\Helper\Config;
use CommerceLeague\ActiveCampaignApi\Exception\NotFoundHttpException;

/**
 * Class TombstoneRelinker
 *
 * Encapsulates the id-preserving relink of a tombstoned ecomCustomer. When an
 * AC contact is deleted, AC anonymizes its linked ecomCustomer (email ->
 * deleted+<n>@example.com, subscriberid -> null) but keeps the record (and its
 * ecomOrders) under the SAME id. Restoring the real email via
 * PUT /api/3/ecomCustomers/{id} keeps the local mapping + order history valid
 * and re-links AC's subscriberid to the live contact.
 *
 * The decision logic is pure and deterministic; the only side effect is the
 * single update() call performed on commit.
 */
class TombstoneRelinker
{
    /**
     * The ecomCustomer id no longer exists in AC.
     */
    public const RESULT_NOT_FOUND = 'not_found';

    /**
     * The record is not (or no longer) a tombstone; never touch it.
     */
    public const RESULT_SKIPPED_NOT_TOMBSTONE = 'skipped_not_tombstone';

    /**
     * No live AC contact exists for the real email; relinking would recreate a
     * deliberately-deleted contact, so skip.
     */
    public const RESULT_NO_LIVE_CONTACT = 'no_live_contact';

    /**
     * Another ecomCustomer already holds the real email; needs a manual merge.
     */
    public const RESULT_SKIPPED_CONFLICT = 'skipped_conflict';

    /**
     * The record is eligible and (on commit) was relinked.
     */
    public const RESULT_RELINKED = 'relinked';

    /**
     * Tombstone email prefix written by AC when a contact is deleted.
     */
    private const TOMBSTONE_EMAIL_PREFIX = 'deleted+';

    public function __construct(
        private readonly Client $client,
        private readonly Config $config
    ) {
    }

    /**
     * Decide and (on commit) perform the id-preserving relink for one target.
     *
     * @return string one of the RESULT_* constants
     */
    public function relink(int $ecomCustomerId, string $realEmail, string $externalId, bool $commit): string
    {
        try {
            $record = $this->client->getCustomerApi()->get($ecomCustomerId);
        } catch (NotFoundHttpException) {
            return self::RESULT_NOT_FOUND;
        }

        $currentEmail = (string)($record['email'] ?? '');
        if (!str_starts_with($currentEmail, self::TOMBSTONE_EMAIL_PREFIX)) {
            return self::RESULT_SKIPPED_NOT_TOMBSTONE;
        }

        $contactPage = $this->client->getContactApi()->listPerPage(
            1,
            0,
            ['filters' => ['email' => $realEmail]]
        );

        if ($contactPage->getItems() === []) {
            return self::RESULT_NO_LIVE_CONTACT;
        }

        $connectionId = $this->config->getConnectionId();

        $customerPage = $this->client->getCustomerApi()->listPerPage(
            1,
            0,
            ['filters' => ['email' => $realEmail, 'connectionid' => $connectionId]]
        );

        foreach ($customerPage->getItems() as $existing) {
            if (isset($existing['id']) && (int)$existing['id'] !== $ecomCustomerId) {
                return self::RESULT_SKIPPED_CONFLICT;
            }
        }

        if ($commit) {
            $this->client->getCustomerApi()->update(
                $ecomCustomerId,
                [
                    'ecomCustomer' => [
                        'connectionid' => $connectionId,
                        'externalid'   => $externalId,
                        'email'        => $realEmail,
                    ],
                ]
            );
        }

        return self::RESULT_RELINKED;
    }
}
