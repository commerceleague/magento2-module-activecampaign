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
    private const XML_PATH_API_ENABLED = 'activecampaign/api/enabled';
    private const XML_PATH_API_URL = 'activecampaign/api/url';
    private const XML_PATH_API_TOKEN = 'activecampaign/api/token';

    /**
     * @return bool
     */
    public function isApiEnabled(): bool
    {
        return (bool)$this->scopeConfig->isSetFlag(self::XML_PATH_API_ENABLED);
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
}
