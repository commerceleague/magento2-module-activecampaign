<?php
declare(strict_types=1);
/**
 */
namespace CommerceLeague\ActiveCampaign\Gateway;

use Throwable;

/**
 * Class ApiCallException
 */
class ApiCallException extends GatewayException
{
    /**
     * @var string
     */
    private $requestData;

    /**
     * @param string $message
     * @param int $code
     * @param Throwable|null $previous
     * @param string $requestData
     */
    public function __construct($message = '', $code = 0, Throwable $previous = null, string $requestData = '')
    {
        $this->requestData = $requestData;
        parent::__construct($message, $code, $previous);
    }

    /**
     * @return string
     */
    public function getRequestData(): string
    {
        return $this->requestData;
    }
}
