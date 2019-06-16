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
    private const XML_PATH_API_KEY = 'activecampaign/general/api_key';

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
    public function getApiKey(): ?string
    {
        return $this->scopeConfig->getValue(self::XML_PATH_API_KEY);
    }
}
