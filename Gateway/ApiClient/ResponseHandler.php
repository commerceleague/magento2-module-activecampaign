<?php
declare(strict_types=1);
/**
 */
namespace CommerceLeague\ActiveCampaign\Gateway\ApiClient;

use CommerceLeague\ActiveCampaign\Gateway\ApiCallException;
use Exception;
use Zend_Http_Response;
use Magento\Framework\Serialize\Serializer\Json as JsonSerializer;

/**
 * Class ResponseHandler
 */
class ResponseHandler
{
    /**
     * @var array
     */
    private static $successResponseCodes = [200, 201];

    /**
     * @var array
     */
    private static $failureResponses = [
        400 => 'Bad Request - The request could not be parsed. Response: %s',
        401 => 'Unauthorized - user is not logged in, could not be authenticated. Response: %s',
        403 => 'Forbidden - Cannot access resource. Response: %s',
        404 => 'Not Found - resource does not exist. Response: %s',
        409 => 'Conflict - with state of the resource on server. Can occur with (too rapid) PUT requests. Response: %s',
        500 => 'Server error. Response: %s'
    ];
    /**
     * @var JsonSerializer
     */
    private $jsonSerializer;

    /**
     * @param JsonSerializer $jsonSerializer
     */
    public function __construct(JsonSerializer $jsonSerializer)
    {
        $this->jsonSerializer = $jsonSerializer;
    }

    /**
     * @param Zend_Http_Response $response
     * @return array
     * @throws ApiCallException
     */
    public function handle(Zend_Http_Response $response): array
    {
        $responseCode = $response->getStatus();

        if (!in_array($responseCode, self::$successResponseCodes)) {
            $errorMessage = $this->buildApiCallFailureMessage($response);
            throw new ApiCallException($errorMessage);
        }

        try {
            $decodedResponseBody = $this->jsonSerializer->unserialize((string)$response->getBody());
        } catch (Exception $e) {
            throw new ApiCallException(
                'Response is not valid JSON: ' . $e->getMessage(),
                $e->getCode(),
                $e
            );
        }

        return $decodedResponseBody;
    }

    /**
     * @param Zend_Http_Response $response
     * @return string
     */
    private function buildApiCallFailureMessage(Zend_Http_Response $response): string
    {
        $responseBody = $response->getBody();

        if (key_exists($response->getStatus(), self::$failureResponses)) {
            return sprintf(self::$failureResponses[$response->getStatus()], $responseBody);
        }

        return sprintf(
            'Unexpected API response code "%s" with content "%s"',
            $response->getStatus(),
            $responseBody
        );
    }
}
