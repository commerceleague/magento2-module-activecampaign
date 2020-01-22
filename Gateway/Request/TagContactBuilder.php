<?php
declare(strict_types=1);
/**
 * Copyright © André Flitsch. All rights reserved.
 * See license.md for license details.
 */

namespace CommerceLeague\ActiveCampaign\Gateway\Request;

use CommerceLeague\ActiveCampaign\Api\Data\ContactInterface;

/**
 * Class TagContactBuilder
 *
 * @package CommerceLeague\ActiveCampaign\Gateway\Request
 */
class TagContactBuilder
{

    /**
     * @param ContactInterface $contact
     * @param int              $tagId
     *
     * @return array
     */
    public function buildWithContact(ContactInterface $contact, int $tagId): array
    {
        return [
            'contact' => $contact->getActiveCampaignId(),
            'tag'     => $tagId
        ];
    }
}