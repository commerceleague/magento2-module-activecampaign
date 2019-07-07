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
    private const XML_PATH_ENABLED = 'activecampaign/general/enabled';
    private const XML_PATH_API_URL = 'activecampaign/general/api_url';
    private const XML_PATH_API_TOKEN = 'activecampaign/general/api_token';
    private const XML_PATH_CONNECTION_ID = 'activecampaign/general/connection_id';
    private const XML_PATH_ABANDONED_CART_EXPORT_AFTER = 'activecampaign/abandoned_cart/export_after';

    /**
     * @return bool
     */
    public function isEnabled(): bool
    {
        return (bool)$this->scopeConfig->isSetFlag(self::XML_PATH_ENABLED);
    }

    /**
     * @return string|null
     */
    public function getApiUrl(): ?string
    {
        return $this->scopeConfig->getValue(self::XML_PATH_API_URL);
    }

    /**
     * @return string|null
     */
    public function getApiToken(): ?string
    {
        return $this->scopeConfig->getValue(self::XML_PATH_API_TOKEN);
    }

    /**
     * @return string|null
     */
    public function getConnectionId(): ?string
    {
        return $this->scopeConfig->getValue(self::XML_PATH_CONNECTION_ID);
    }

    /**
     * @return string|null
     */
    public function getAbandonedCartExportAfter(): ?string
    {
        return $this->scopeConfig->getValue(self::XML_PATH_ABANDONED_CART_EXPORT_AFTER);
    }
}
