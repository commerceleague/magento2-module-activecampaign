<?php
declare(strict_types=1);
/**
 */
namespace CommerceLeague\ActiveCampaign\Gateway\Endpoint;

use CommerceLeague\ActiveCampaign\Gateway\ApiCallException;
use CommerceLeague\ActiveCampaign\Gateway\ApiClient;
use CommerceLeague\ActiveCampaign\Gateway\GatewayException;
use Zend_Http_Client;
use Zend_Http_Client_Exception;

/**
 * Class Contact
 */
class ContactEndpoint
{
    /**
     * @var ApiClient
     */
    private $client;

    /**
     * @param ApiClient $client
     */
    public function __construct(ApiClient $client)
    {
        $this->client = $client;
    }

    /**
     * @param array $contact
     * @return int
     * @throws ApiCallException
     * @throws GatewayException
     * @throws Zend_Http_Client_Exception
     */
    public function sync(array $contact): int
    {
        $response = $this->client->makeApiCall(
            '/api/3/contact/sync',
            Zend_Http_Client::POST,
            ['contact' => $contact]
        );

        if (!isset($response['contact']) || !isset($response['contact']['id'])) {
            throw new GatewayException('Expected field "id" is missing.');
        }

        return (int)$response['contact']['id'];
    }
}
