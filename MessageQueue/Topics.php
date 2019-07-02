<?php
declare(strict_types=1);
/**
 */

namespace CommerceLeague\ActiveCampaign\MessageQueue;

/**
 * Class Topics
 */
class Topics
{
    public const CONTACT_CREATE_UPDATE = 'activecampaign.contact.create_update';
    public const CONTACT_IMPORT = 'activecampaign.contact.import';
}
