<?php
declare(strict_types=1);
/**
 */

namespace CommerceLeague\ActiveCampaign\Helper;

use Magento\Framework\App\Helper\AbstractHelper;

/**
 * Class Config
 */
class Config extends AbstractHelper
{

    private const XML_PATH_GENERAL_ENABLED       = 'activecampaign/general/enabled';
    private const XML_PATH_GENERAL_API_URL       = 'activecampaign/general/api_url';
    private const XML_PATH_GENERAL_API_TOKEN     = 'activecampaign/general/api_token';
    private const XML_PATH_GENERAL_CONNECTION_ID = 'activecampaign/general/connection_id';

    private const XML_PATH_EXPORT_CONTACT_ENABLED        = 'activecampaign/export/contact_enabled';
    private const XML_PATH_EXPORT_CUSTOMER_ENABLED       = 'activecampaign/export/customer_enabled';
    private const XML_PATH_EXPORT_ORDER_ENABLED          = 'activecampaign/export/order_enabled';
    private const XML_PATH_EXPORT_ABANDONED_CART_ENABLED = 'activecampaign/export/abandoned_cart_enabled';

    private const XML_PATH_WEBHOOK_ENABLED = 'activecampaign/webhook/enabled';
    private const XML_PATH_WEBHOOK_TOKEN   = 'activecampaign/webhook/token';

    private const XML_PATH_CUSTOMER_LIST_ID = 'activecampaign/customer_export/customer_list_id';

    private const XML_PATH_NEWSLETTER_SUBSCRIBER_LIST = 'activecampaign/newsletter_export/newsletter_subscribers_list';
    private const XML_PATH_NEWSLETTER_SUBSCRIBER_TAGS = 'activecampaign/newsletter_export/newsletter_subscribers_tags';

    /**
     * @return bool
     */
    public function isEnabled(): bool
    {
        return (bool)$this->scopeConfig->isSetFlag(self::XML_PATH_GENERAL_ENABLED);
    }

    /**
     * @return string|null
     */
    public function getApiUrl(): ?string
    {
        return $this->scopeConfig->getValue(self::XML_PATH_GENERAL_API_URL);
    }

    /**
     * @return string|null
     */
    public function getApiToken(): ?string
    {
        return $this->scopeConfig->getValue(self::XML_PATH_GENERAL_API_TOKEN);
    }

    /**
     * @return string|null
     */
    public function getConnectionId(): ?string
    {
        return $this->scopeConfig->getValue(self::XML_PATH_GENERAL_CONNECTION_ID);
    }

    /**
     * @return bool
     */
    public function isContactExportEnabled(): bool
    {
        return (bool)$this->scopeConfig->isSetFlag(self::XML_PATH_EXPORT_CONTACT_ENABLED);
    }

    /**
     * @return bool
     */
    public function isCustomerExportEnabled(): bool
    {
        return (bool)$this->scopeConfig->isSetFlag(self::XML_PATH_EXPORT_CUSTOMER_ENABLED);
    }

    /**
     * @return bool
     */
    public function isOrderExportEnabled(): bool
    {
        return (bool)$this->scopeConfig->isSetFlag(self::XML_PATH_EXPORT_ORDER_ENABLED);
    }

    /**
     * @return bool
     */
    public function isAbandonedCartExportEnabled(): bool
    {
        return (bool)$this->scopeConfig->isSetFlag(self::XML_PATH_EXPORT_ABANDONED_CART_ENABLED);
    }

    /**
     * @return bool
     */
    public function isWebhookEnabled(): bool
    {
        return (bool)$this->scopeConfig->isSetFlag(self::XML_PATH_WEBHOOK_ENABLED);
    }

    /**
     * @return string|null
     */
    public function getWebhookToken(): ?string
    {
        return $this->scopeConfig->getValue(self::XML_PATH_WEBHOOK_TOKEN);
    }

    /**
     * Get the list id for registered customers, if set
     *
     * @return int|null
     */
    public function getCustomerListId(): ?int
    {
        return $this->scopeConfig->getValue(self::XML_PATH_CUSTOMER_LIST_ID);
    }

    /**
     * Get the list id for newsletter subscribers
     *
     * @return int|null
     */
    public function getNewsletterSubscriberList(): ?int
    {
        return $this->scopeConfig->getValue(self::XML_PATH_NEWSLETTER_SUBSCRIBER_LIST);
    }

    /**
     * Get the tags selected to be added to the Newsletter subscriber
     *
     * @return array|null
     */
    public function getNewsletterSubscriberTags(): ?array
    {
        $tags = $this->scopeConfig->getValue(self::XML_PATH_NEWSLETTER_SUBSCRIBER_TAGS);
        if (null == $tags) {
            return $tags;
        }

        return explode(',', $tags);
    }
}
