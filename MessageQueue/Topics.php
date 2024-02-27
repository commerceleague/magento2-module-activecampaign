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
    final public const CUSTOMER_CONTACT_EXPORT = 'activecampaign.customer.export.contact';
    final public const CUSTOMER_CUSTOMER_EXPORT = 'activecampaign.customer.export.customer';
    final public const GUEST_CUSTOMER_EXPORT = 'activecampaign.customer.export.guest.customer';
    final public const NEWSLETTER_CONTACT_EXPORT = 'activecampaign.newsletter.export.contact';
    final public const SALES_ORDER_EXPORT = 'activecampaign.sales.export.order';
    final public const QUOTE_ABANDONED_CART_EXPORT = 'activecampaign.quote.export.abandoned_cart';
    final public const ASSIGN_CONTACT_TO_LIST = 'activecampaign.customer.assign.contact.to.list';
    final public const ASSIGN_NEWSLETTER_SUBSCRIBER_TO_LIST = 'activecampaign.newsletter.assign.subscriber.to.list';
    final public const TAG_NEWSLETTER_SUBSCRIBER = 'activecampaign.newsletter.tag.subscriber';
}
