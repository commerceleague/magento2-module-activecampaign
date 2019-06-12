<?php
declare(strict_types=1);
/**
 */
namespace CommerceLeague\ActiveCampaign\Logger;

use Magento\Framework\Logger\Handler\Base as BaseHandler;

/**
 * Class Handler
 */
class Handler extends BaseHandler
{
    /**
     * @var int
     */
    protected $loggerType = Logger::DEBUG;

    /**
     * @var string
     */
    protected $fileName = '/var/log/activecampaign.log';
}
