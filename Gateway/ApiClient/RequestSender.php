<?php
declare(strict_types=1);
/**
 */

namespace CommerceLeague\ActiveCampaign\Gateway\ApiClient;

use CommerceLeague\ActiveCampaign\Gateway\ApiCallException;
use Exception;
use Magento\Framework\HTTP\ZendClient;
use Zend_Http_Response;

/**
 * Class RequestSender
 */
class RequestSender
{
    /**
     * @param ZendClient $client
     * @return Zend_Http_Response
     * @throws ApiCallException
     */
    public function send(ZendClient $client): Zend_Http_Response
    {
        try {
            return $client->request();
        } catch (Exception $e) {
            throw new ApiCallException(
                'Unable to process request: ' . $e->getMessage(),
                $e->getCode(),
                $e,
                $client->getLastRequest()
            );
        }
    }
}
