<?php
declare(strict_types=1);
namespace CommerceLeague\ActiveCampaign\Model\Export;

class UnprocessableOutcome
{
    public const TYPE_DUPLICATE  = 'duplicate';
    public const TYPE_VALIDATION = 'validation';
    public const TYPE_UNKNOWN    = 'unknown';

    /** @param array<string,mixed> $payload resolved duplicate entity when TYPE_DUPLICATE */
    public function __construct(
        public readonly string $type,
        public readonly ?string $code = null,
        public readonly ?string $message = null,
        public readonly array $payload = []
    ) {}

    public function isDuplicate(): bool { return $this->type === self::TYPE_DUPLICATE; }
}
