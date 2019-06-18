<?php
declare(strict_types=1);
/**
 */
namespace CommerceLeague\ActiveCampaign\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Store\Model\StoreManagerInterface;

/**
 * Class Config
 */
class Config extends AbstractHelper
{
    private const XML_PATH_API_ENABLED = 'activecampaign/api/enabled';
    private const XML_PATH_API_URL = 'activecampaign/api/url';
    private const XML_PATH_API_TOKEN = 'activecampaign/api/token';
    private const XML_PATH_EVENT_TRACKING_ENABLED = 'activecampaign/event_tracking/enabled';
    private const XML_PATH_EVENT_TRACKING_ID = 'activecampaign/event_tracking/id';
    private const XML_PATH_EVENT_TRACKING_KEY = 'activecampaign/event_tracking/key';

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * @param Context $context
     * @param StoreManagerInterface $storeManager
     */
    public function __construct(
        Context $context,
        StoreManagerInterface $storeManager
    ) {
        $this->storeManager = $storeManager;
        parent::__construct($context);
    }

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

    /**
     * @return bool
     */
    public function isEventTrackingEnabled(): bool
    {
        return (bool)$this->scopeConfig->isSetFlag(self::XML_PATH_EVENT_TRACKING_ENABLED);
    }

    /**
     * @return string|null
     */
    public function getEventTrackingId(): ?string
    {
        return $this->scopeConfig->getValue(self::XML_PATH_EVENT_TRACKING_ID);
    }

    /**
     * @return string|null
     */
    public function getEventTrackingKey(): ?string
    {
        return $this->scopeConfig->getValue(self::XML_PATH_EVENT_TRACKING_KEY);
    }
}
