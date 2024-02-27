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
     * AbstractConsumer constructor.
     */
    public function __construct(private readonly Logger $logger)
    {
    }

    /**
     * @param                                  $request
     */
    public function logUnprocessableEntityHttpException(
        UnprocessableEntityHttpException $unprocessableEntityHttpException,
        $request
    ) {
        $this->logger->error(self::class);
        $this->logger->error($unprocessableEntityHttpException->getMessage());
        $this->logger->error(print_r($unprocessableEntityHttpException->getResponseErrors(), true));
        $this->logger->error(print_r($request, true));
    }

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