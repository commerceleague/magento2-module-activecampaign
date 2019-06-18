<?php
declare(strict_types=1);
/**
 */
namespace CommerceLeague\ActiveCampaign\Gateway\ApiClient;

use CommerceLeague\ActiveCampaign\Gateway\ApiCallException;
use Zend_Http_Client_Exception;

/**
 * Class RequestBuilder
 */
class RequestBuilder
{
    /**
     * @var HttpClientFactory
     */
    private $httpClientFactory;

    /**
     * @var RequestSender
     */
    private $requestSender;

    /**
     * @var ResponseHandler
     */
    private $responseHandler;

    /**
     * @param HttpClientFactory $httpClientFactory
     * @param RequestSender $requestSender
     * @param ResponseHandler $responseHandler
     */
    public function __construct(
        HttpClientFactory $httpClientFactory,
        RequestSender $requestSender,
        ResponseHandler $responseHandler
    ) {
        $this->httpClientFactory = $httpClientFactory;
        $this->requestSender = $requestSender;
        $this->responseHandler = $responseHandler;
    }

    /**
     * @param string $url
     * @param string $method
     * @param array $params
     * @return array
     * @throws ApiCallException
     * @throws Zend_Http_Client_Exception
     */
    public function doRequest(string $url, string $method, array $params = []): array
    {
        $client = $this->httpClientFactory->create($url, $method, $params);
        $response = $this->requestSender->send($client);
        return $this->responseHandler->handle($response);
    }
}
