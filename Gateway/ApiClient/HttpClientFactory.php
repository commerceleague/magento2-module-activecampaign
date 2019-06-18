<?php
declare(strict_types=1);
/**
 */
namespace CommerceLeague\ActiveCampaign\Gateway\ApiClient;

use CommerceLeague\ActiveCampaign\Helper\Config as ConfigHelper;
use Magento\Framework\HTTP\ZendClient;
use Magento\Framework\HTTP\ZendClientFactory;
use Magento\Framework\Serialize\Serializer\Json as JsonSerializer;
use Zend_Http_Client_Exception;

/**
 * Class HttpClientFactory
 */
class HttpClientFactory
{
    private const URL_SEPARATOR = '/';

    /**
     * @var ConfigHelper
     */
    private $configHelper;

    /**
     * @var ZendClientFactory
     */
    private $clientFactory;

    /**
     * @var JsonSerializer
     */
    private $jsonSerializer;

    /**
     * @param ConfigHelper $configHelper
     * @param ZendClientFactory $clientFactory
     * @param JsonSerializer $jsonSerializer
     */
    public function __construct(
        ConfigHelper $configHelper,
        ZendClientFactory $clientFactory,
        JsonSerializer $jsonSerializer

    ) {
        $this->configHelper = $configHelper;
        $this->clientFactory = $clientFactory;
        $this->jsonSerializer = $jsonSerializer;
    }

    /**
     * @param string $url
     * @param string $method
     * @param array $params
     * @return ZendClient
     * @throws Zend_Http_Client_Exception
     */
    public function create(string $url, string $method, array $params = []): ZendClient
    {
        $apiToken = $this->configHelper->getApiToken();
        $apiUrl = $this->buildFullApiUrl($url);

        /** @var ZendClient $client */
        $client = $this->clientFactory->create();
        $client->setHeaders([
            'User-Agent' => 'activecampaign-v3-php/1.0',
            'Api-Token' => $apiToken,
            'Accept' => 'application/json'
        ]);

        if (!empty($params)) {
            $encodedData = $this->jsonSerializer->serialize($params);
            $client->setRawData($encodedData, 'application/json');
        }

        $client->setMethod($method);
        $client->setUri($apiUrl);

        return $client;
    }

    /**
     * @param string $url
     * @return string
     */
    private function buildFullApiUrl(string $url): string
    {
        return $this->getBaseApiUrl() . self::URL_SEPARATOR . ltrim($url, self::URL_SEPARATOR);
    }

    /**
     * @return string
     */
    private function getBaseApiUrl(): string
    {
        return rtrim($this->configHelper->getApiUrl(), self::URL_SEPARATOR);
    }
}
