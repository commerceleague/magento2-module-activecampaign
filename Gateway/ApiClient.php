<?php
declare(strict_types=1);
/**
 */
namespace CommerceLeague\ActiveCampaign\Gateway;

use CommerceLeague\ActiveCampaign\Gateway\ApiClient\RequestBuilder;
use Zend_Http_Client_Exception;

/**
 * Class ApiClient
 */
class ApiClient
{
    /**
     * @var RequestBuilder
     */
    private $requestBuilder;

    /**
     * @param RequestBuilder $requestBuilder
     */
    public function __construct(RequestBuilder $requestBuilder)
    {
        $this->requestBuilder = $requestBuilder;
    }

    /**
     * @param string $url
     * @param string $method
     * @param array $params
     * @return array
     * @throws ApiCallException
     * @throws Zend_Http_Client_Exception
     */
    public function makeApiCall(string $url, string $method, array $params = []): array
    {
        return $this->requestBuilder->doRequest($url, $method, $params);
    }
}
