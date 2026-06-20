<?php
declare(strict_types=1);
/**
 */

namespace CommerceLeague\ActiveCampaign\Model\Export;

use CommerceLeague\ActiveCampaignApi\Exception\UnprocessableEntityHttpException;

/**
 * Thrown when a 422 "duplicate" was reported by ActiveCampaign but the follow-up
 * lookup could not resolve the existing entity (empty result set). Extends
 * UnprocessableEntityHttpException so the existing consumer catch blocks treat it
 * as a (now logged) unprocessable outcome instead of fatally dereferencing an
 * empty items array.
 *
 * The parent HttpException constructor requires PSR-7 request/response objects
 * which are unavailable in this synthetic case, so it is intentionally bypassed
 * and only the message, code and response errors are populated.
 */
class DuplicateNotFoundException extends UnprocessableEntityHttpException
{
    /**
     * @var array<int,array<string,mixed>>
     */
    private array $responseErrors;

    /**
     * @param array<int,array<string,mixed>> $responseErrors
     */
    public function __construct(array $responseErrors = [['code' => 'duplicate_not_found']])
    {
        $this->responseErrors = $responseErrors;
        $this->message = 'Duplicate entity could not be resolved.';
        $this->code = 422;
    }

    /**
     * @return array<int,array<string,mixed>>
     */
    public function getResponseErrors(): array
    {
        return $this->responseErrors;
    }
}
