<?php

declare(strict_types=1);

namespace CommerceLeague\ActiveCampaign\Helper;

/**
 * ActiveCampaign contact status constants.
 */
final class Constants
{
    public const CONTACT_STATUS_ANY = '-1';
    public const CONTACT_STATUS_UNCONFIRMED = '0';
    public const CONTACT_STATUS_ACTIVE = '1';
    public const CONTACT_STATUS_UNSUBSCRIBED = '2';
    public const CONTACT_STATUS_BOUNCED = '3';
}
