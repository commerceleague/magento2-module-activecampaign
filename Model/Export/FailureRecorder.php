<?php
declare(strict_types=1);
/**
 */

namespace CommerceLeague\ActiveCampaign\Model\Export;

use CommerceLeague\ActiveCampaign\Api\Data\FailureTrackableInterface;
use CommerceLeague\ActiveCampaign\Helper\Config;
use Magento\Framework\Stdlib\DateTime\DateTime;

/**
 * Class FailureRecorder
 *
 * Records export failure/success metadata on a mapping entity. The caller is
 * responsible for persisting the entity via its repository afterwards.
 */
class FailureRecorder
{
    public function __construct(
        private readonly Config $config,
        private readonly DateTime $dateTime
    ) {
    }

    /**
     * Record one failed export attempt on the entity (caller persists via its repository).
     */
    public function recordFailure(
        FailureTrackableInterface $entity,
        string $errorCode,
        ?string $errorMessage
    ): void {
        $attempts = (int)$entity->getExportAttempts() + 1;
        $entity->setExportAttempts($attempts);
        $entity->setLastErrorCode($errorCode);
        $entity->setLastErrorMessage($errorMessage !== null ? mb_substr($errorMessage, 0, 255) : null);
        $entity->setLastAttemptedAt($this->dateTime->gmtDate());

        $maxAttempts = $this->config->getMaxExportAttempts();
        if ($this->config->isDeadLetterEnabled() && $maxAttempts > 0 && $attempts >= $maxAttempts) {
            $entity->setExportStatus(FailureTrackableInterface::EXPORT_STATUS_FAILED);
        }
    }

    /**
     * Record a successful export (caller persists via its repository).
     */
    public function recordSuccess(FailureTrackableInterface $entity): void
    {
        $entity->setExportStatus(FailureTrackableInterface::EXPORT_STATUS_SYNCED);
        $entity->setExportAttempts(0);
        $entity->setLastErrorCode(null);
        $entity->setLastErrorMessage(null);
    }
}
