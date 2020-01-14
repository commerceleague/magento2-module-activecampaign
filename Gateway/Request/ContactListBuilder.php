<?php
declare(strict_types=1);
/**
 * Copyright © André Flitsch. All rights reserved.
 * See license.md for license details.
 */

namespace CommerceLeague\ActiveCampaign\Gateway\Request;

use CommerceLeague\ActiveCampaign\Api\Data\ContactInterface;

/**
 * Class ContactListBuilder
 *
 * @package CommerceLeague\ActiveCampaign\Gateway\Request
 */
class ContactListBuilder
{

    /**
     * Build the contactList Request with Contact
     *
     * @param ContactInterface $contact
     * @param int              $listId
     * @param int              $status
     *
     * @return array
     */
    public function buildWithContact(ContactInterface $contact, int $listId, int $status = 1): array
    {
        return [
            'list'    => $listId,
            'contact' => $contact->getId(),
            'status'  => $status
        ];
    }
}