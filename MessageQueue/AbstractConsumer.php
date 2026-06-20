<?php
declare(strict_types=1);
/**
 * Copyright © André Flitsch. All rights reserved.
 * See license.md for license details.
 */

namespace CommerceLeague\ActiveCampaign\MessageQueue;

use CommerceLeague\ActiveCampaign\Logger\Logger;
use CommerceLeague\ActiveCampaign\Model\Export\UnprocessableOutcome;
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

    public function getLogger(): Logger
    {
        return $this->logger;
    }

    public function logException(Exception $exception): void
    {
        $this->getLogger()->error($exception);
    }

    /**
     * Returns the ActiveCampaign id as a positive int, or null when the value is
     * missing, non-numeric or not greater than zero. AC ids are always positive,
     * so a sentinel like 0/"0" must never be persisted.
     */
    protected function extractActiveCampaignId(mixed $value): ?int
    {
        if (!is_numeric($value)) {
            return null;
        }

        $id = (int)$value;

        return $id > 0 ? $id : null;
    }

    /**
     * @param array<mixed> $request
     * @return mixed
     */
    public function logUnprocessableEntityHttpException(
        UnprocessableEntityHttpException $unprocessableEntityHttpException,
        array                            $request
    ): mixed {
        $this->getLogger()->error(static::class);
        $this->getLogger()->error($unprocessableEntityHttpException->getMessage());
        $this->getLogger()->error(print_r($unprocessableEntityHttpException->getResponseErrors(), true));
        $this->getLogger()->error(print_r($request, true));
        return null;
    }

    /**
     * Resolves the existing ActiveCampaign entity behind a 422 "duplicate" error.
     *
     * Implementations that recover a duplicate return `[$key => $resolvedItem]`
     * (where `$resolvedItem` is the AC entity array containing an `'id'`); stub
     * implementations that do not recover may return void. The return value is
     * passed through to UnprocessableOutcome::$payload, so a non-array return
     * simply yields an empty payload and is treated as "not resolved".
     *
     * @param array<mixed> $request
     *
     * @return array<string,mixed>|void
     */
    abstract function processDuplicateEntity(array $request, string $key);

    /**
     *
     * @param array<mixed> $request
     */
    protected function handleUnprocessableEntityHttpException(
        UnprocessableEntityHttpException $e,
        array                            $request,
        string                           $key
    ): UnprocessableOutcome {
        $errors  = $e->getResponseErrors();
        $first   = array_shift($errors);
        $code    = is_array($first) ? ($first['code'] ?? null) : null;
        $message = is_array($first) ? ($first['title'] ?? $first['message'] ?? null) : null;

        if ($code === self::ERROR_CODE_DUPLICATE) {
            $resolved = $this->processDuplicateEntity($request, $key);
            return new UnprocessableOutcome(
                UnprocessableOutcome::TYPE_DUPLICATE,
                $code,
                $message,
                is_array($resolved) ? $resolved : []
            );
        }
        if ($code !== null) {
            return new UnprocessableOutcome(UnprocessableOutcome::TYPE_VALIDATION, $code, $message);
        }
        return new UnprocessableOutcome(UnprocessableOutcome::TYPE_UNKNOWN, null, $message);
    }
}
