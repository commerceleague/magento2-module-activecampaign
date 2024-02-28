<?php
declare(strict_types=1);
/**
 */

namespace CommerceLeague\ActiveCampaign\Setup;

/**
 * Interface SchemaInterface
 */
interface SchemaInterface
{

    public const CONTACT_TABLE        = 'activecampaign_contact';
    public const CUSTOMER_TABLE       = 'activecampaign_customer';
    public const ORDER_TABLE          = 'activecampaign_order';
    public const GUEST_CUSTOMER_TABLE = 'activecampaign_guest_customer';

    public const ABANDONED_CART_TABLE = 'activecampaign_abandoned_cart';
}
