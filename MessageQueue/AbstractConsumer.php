<?php
declare(strict_types=1);
/**
 * Copyright © André Flitsch. All rights reserved.
 * See license.md for license details.
 */

namespace CommerceLeague\ActiveCampaign\MessageQueue;

use CommerceLeague\ActiveCampaign\Logger\Logger;
use CommerceLeague\ActiveCampaignApi\Exception\UnprocessableEntityHttpException;
use Exception;

/**
 * Class AbstractConsumer
 *
 * @package CommerceLeague\ActiveCampaign\MessageQueue
 */
abstract class AbstractConsumer
{

    /**
     * @var Logger
     */
    private $logger;

    /**
     * AbstractConsumer constructor.
     *
     * @param Logger $logger
     */
    public function __construct(Logger $logger)
    {
        $this->logger = $logger;
    }

    /**
     * @param UnprocessableEntityHttpException $unprocessableEntityHttpException
     * @param                                  $request
     */
    public function logUnprocessableEntityHttpException(
        UnprocessableEntityHttpException $unprocessableEntityHttpException,
        $request
    ) {
        $this->logger->error(__CLASS__);
        $this->logger->error($unprocessableEntityHttpException->getMessage());
        $this->logger->error(print_r($unprocessableEntityHttpException->getResponseErrors(), true));
        $this->logger->error(print_r($request, true));
    }

    /**
     * @param Exception $exception
     */
    public function logException(Exception $exception)
    {
        $this->logger->error($exception);
    }

    /**
     * @return Logger
     */
    public function getLogger(): Logger
    {
        return $this->logger;
    }
}