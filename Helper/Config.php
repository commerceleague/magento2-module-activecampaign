<?php
declare(strict_types=1);
/**
 */

namespace CommerceLeague\ActiveCampaign\Helper;

use Magento\Customer\Api\AccountManagementInterface;
use Magento\Customer\Model\AccountConfirmation;
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\Exception\LocalizedException;

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

    private const XML_PATH_EXPORT_ORDER_STATUSES   = 'activecampaign/order_export/filter_order_statuses';
    private const XML_PATH_EXPORT_ORDER_START_DATE = 'activecampaign/order_export/filter_date_from';

    private const XML_PATH_WEBHOOK_ENABLED = 'activecampaign/webhook/enabled';
    private const XML_PATH_WEBHOOK_TOKEN   = 'activecampaign/webhook/token';

    private const XML_PATH_CUSTOMER_LIST_ID               = 'activecampaign/customer_export/customer_list_id';
    private const XML_PATH_CUSTOMER_ALLOWED_GROUP_ID_LIST = 'activecampaign/customer_export/allowed_group_id_list';

    private const XML_PATH_NEWSLETTER_SUBSCRIBER_LIST = 'activecampaign/newsletter_export/newsletter_subscribers_list';
    private const XML_PATH_NEWSLETTER_SUBSCRIBER_TAGS = 'activecampaign/newsletter_export/newsletter_subscribers_tags';

    /**
     * @var AccountConfirmation
     */
    private $accountConfirmation;

    /**
     * Config constructor.
     *
     * @param Context                    $context
     * @param AccountManagementInterface $accountManagement
     */
    public function __construct(
        Context $context,
        private readonly AccountManagementInterface $accountManagement
    ) {
        parent::__construct($context);
    }

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
        $listId = $this->scopeConfig->getValue(self::XML_PATH_CUSTOMER_LIST_ID);
        if (null !== $listId) {
            return (int)$listId;
        }
        return $listId;
    }

    /**
     * Get the list id for newsletter subscribers
     *
     * @return int|null
     */
    public function getNewsletterSubscriberList(): ?int
    {
        $listId = $this->scopeConfig->getValue(self::XML_PATH_NEWSLETTER_SUBSCRIBER_LIST);
        if (null !== $listId) {
            return (int)$listId;
        }
        return $listId;
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

        return explode(',', (string) $tags);
    }

    /**
     * Is customer confirmation required
     *
     *
     * @return bool
     * @throws LocalizedException
     */
    public function isConfirmationRequired(int $customerId): bool
    {
        $status                 = $this->accountManagement->getConfirmationStatus($customerId);
        $noConfirmationRequired = [
            AccountManagementInterface::ACCOUNT_CONFIRMATION_NOT_REQUIRED,
            AccountManagementInterface::ACCOUNT_CONFIRMED
        ];
        if (in_array($status, $noConfirmationRequired)) {
            return false;
        }
        return true;
    }

    /**
     * Get the set order status filters
     *
     * @return array|null
     */
    public function getOrderExportStatuses(): ?array
    {
        $orderStatuses = $this->scopeConfig->getValue(self::XML_PATH_EXPORT_ORDER_STATUSES);
        if (null !== $orderStatuses) {
            return explode(',', $orderStatuses);
        }
        return null;
    }

    /**
     * Get the set order export start date filter
     *
     * @return string|null
     */
    public function getOrderExportStartDate(): ?string
    {
        return $this->scopeConfig->getValue(self::XML_PATH_EXPORT_ORDER_START_DATE);
    }

    public function getAllowedCustomerGroupIds(): array
    {
        $list = $this->scopeConfig->getValue(self::XML_PATH_CUSTOMER_ALLOWED_GROUP_ID_LIST);
        if ($list) {
            return explode(',', $list);
        }
        return [];
    }

    public function isConnectionSet(): bool
    {
        $token  = $this->getApiToken();
        $apiUrl = $this->getApiUrl();

        if ($token !== null && $apiUrl !== null) {
            return true;
        }
        return false;
    }
}
