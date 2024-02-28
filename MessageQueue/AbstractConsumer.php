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

    final public const RESPONSE_KEY_CUSTOMER = 'ecomCustomer';
    final public const RESPONSE_KEY_ORDER    = 'ecomOrder';
    final public const RESPONSE_KEY_CONTACT  = 'contact';
    final public const ERROR_CODE_DUPLICATE  = 'duplicate';

    /**
     * AbstractConsumer constructor.
     */
    public function __construct(private readonly Logger $logger)
    {
    }

    /**
     * @return Logger
     */
    public function getLogger(): Logger
    {
        return $this->logger;
    }

    public function logException(Exception $exception): void
    {
        $this->getLogger()->error($exception);
    }

    /**
     * @param array<mixed>                     $request
     * @return void
     */
    public function logUnprocessableEntityHttpException(
        UnprocessableEntityHttpException $unprocessableEntityHttpException,
        array                            $request
    ): mixed {
        $this->getLogger()->error(static::class);
        $this->getLogger()->error($unprocessableEntityHttpException->getMessage());
        $this->getLogger()->error(print_r($unprocessableEntityHttpException->getResponseErrors(), true));
        $this->getLogger()->error(print_r($request, true));
    }

    /**
     *
     * @param array<mixed> $request
     *
     * @return mixed
     */
    abstract function processDuplicateEntity(array $request, string $key);

    /**
     *
     * @param array<mixed>                     $request
     *
     * @return array
     */
    protected function handleUnprocessableEntityHttpException(
        UnprocessableEntityHttpException $e,
        array                            $request,
        string                           $key
    ) {
        $errors    = $e->getResponseErrors();
        $errors    = array_shift($errors);
        $errorCode = $errors['code'];

        if ($errorCode == self::ERROR_CODE_DUPLICATE) {
            return $this->processDuplicateEntity($request, $key);
        }
    }
}
