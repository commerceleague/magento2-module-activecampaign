<?php
declare(strict_types=1);
/**
 */

namespace CommerceLeague\ActiveCampaign\Test\Unit\Model\Export;

use CommerceLeague\ActiveCampaign\Api\Data\FailureTrackableInterface;
use CommerceLeague\ActiveCampaign\Helper\Config;
use CommerceLeague\ActiveCampaign\Model\Export\FailureRecorder;
use CommerceLeague\ActiveCampaign\Test\Unit\AbstractTestCase;
use Magento\Framework\Stdlib\DateTime\DateTime;
use PHPUnit\Framework\MockObject\MockObject;

class FailureRecorderTest extends AbstractTestCase
{
    private const FIXED_DATE = '2026-06-20 12:00:00';

    /**
     * @var MockObject|Config
     */
    private $config;

    /**
     * @var MockObject|DateTime
     */
    private $dateTime;

    /**
     * @var FailureRecorder
     */
    private $failureRecorder;

    protected function setUp(): void
    {
        $this->config = $this->createMock(Config::class);
        $this->dateTime = $this->createMock(DateTime::class);

        $this->dateTime->expects($this->any())
            ->method('gmtDate')
            ->willReturn(self::FIXED_DATE);

        $this->failureRecorder = new FailureRecorder($this->config, $this->dateTime);
    }

    public function testRecordFailureIncrementsAttemptsAndSetsFields(): void
    {
        $entity = $this->createMock(FailureTrackableInterface::class);

        $entity->expects($this->once())
            ->method('getExportAttempts')
            ->willReturn(2);

        $entity->expects($this->once())
            ->method('setExportAttempts')
            ->with(3);

        $entity->expects($this->once())
            ->method('setLastErrorCode')
            ->with('http_error');

        $entity->expects($this->once())
            ->method('setLastErrorMessage')
            ->with('boom');

        $entity->expects($this->once())
            ->method('setLastAttemptedAt')
            ->with(self::FIXED_DATE);

        $this->config->expects($this->any())
            ->method('getMaxExportAttempts')
            ->willReturn(0);

        $entity->expects($this->never())
            ->method('setExportStatus');

        $this->failureRecorder->recordFailure($entity, 'http_error', 'boom');
    }

    public function testRecordFailureTruncatesLongMessageTo255(): void
    {
        $entity = $this->createMock(FailureTrackableInterface::class);

        $longMessage = str_repeat('x', 300);
        $expected = str_repeat('x', 255);

        $entity->expects($this->any())
            ->method('getExportAttempts')
            ->willReturn(0);

        $entity->expects($this->once())
            ->method('setLastErrorMessage')
            ->with($expected);

        $this->config->expects($this->any())
            ->method('getMaxExportAttempts')
            ->willReturn(0);

        $this->failureRecorder->recordFailure($entity, 'unknown', $longMessage);
    }

    public function testRecordFailureAllowsNullMessage(): void
    {
        $entity = $this->createMock(FailureTrackableInterface::class);

        $entity->expects($this->any())
            ->method('getExportAttempts')
            ->willReturn(0);

        $entity->expects($this->once())
            ->method('setLastErrorMessage')
            ->with(null);

        $this->config->expects($this->any())
            ->method('getMaxExportAttempts')
            ->willReturn(0);

        $this->failureRecorder->recordFailure($entity, 'unknown', null);
    }

    public function testRecordFailureNeverDeadLettersWhenMaxAttemptsZero(): void
    {
        $entity = $this->createMock(FailureTrackableInterface::class);

        // Even after many attempts, default config (max=0) never marks FAILED.
        $entity->expects($this->any())
            ->method('getExportAttempts')
            ->willReturn(99);

        $this->config->expects($this->any())
            ->method('getMaxExportAttempts')
            ->willReturn(0);

        $this->config->expects($this->any())
            ->method('isDeadLetterEnabled')
            ->willReturn(true);

        $entity->expects($this->never())
            ->method('setExportStatus');

        $this->failureRecorder->recordFailure($entity, 'unknown', 'msg');
    }

    public function testRecordFailureDeadLettersWhenAttemptsReachMax(): void
    {
        $entity = $this->createMock(FailureTrackableInterface::class);

        // attempts becomes 3 (2 + 1), max=3 -> FAILED.
        $entity->expects($this->any())
            ->method('getExportAttempts')
            ->willReturn(2);

        $this->config->expects($this->any())
            ->method('getMaxExportAttempts')
            ->willReturn(3);

        $this->config->expects($this->any())
            ->method('isDeadLetterEnabled')
            ->willReturn(true);

        $entity->expects($this->once())
            ->method('setExportStatus')
            ->with(FailureTrackableInterface::EXPORT_STATUS_FAILED);

        $this->failureRecorder->recordFailure($entity, 'unknown', 'msg');
    }

    public function testRecordFailureDoesNotDeadLetterBeforeMax(): void
    {
        $entity = $this->createMock(FailureTrackableInterface::class);

        // attempts becomes 2 (1 + 1), max=3 -> not yet FAILED.
        $entity->expects($this->any())
            ->method('getExportAttempts')
            ->willReturn(1);

        $this->config->expects($this->any())
            ->method('getMaxExportAttempts')
            ->willReturn(3);

        $this->config->expects($this->any())
            ->method('isDeadLetterEnabled')
            ->willReturn(true);

        $entity->expects($this->never())
            ->method('setExportStatus');

        $this->failureRecorder->recordFailure($entity, 'unknown', 'msg');
    }

    public function testRecordFailureNeverDeadLettersWhenDeadLetterDisabled(): void
    {
        $entity = $this->createMock(FailureTrackableInterface::class);

        // attempts becomes 3, max=3, but dead-letter disabled -> never FAILED.
        $entity->expects($this->any())
            ->method('getExportAttempts')
            ->willReturn(2);

        $this->config->expects($this->any())
            ->method('getMaxExportAttempts')
            ->willReturn(3);

        $this->config->expects($this->any())
            ->method('isDeadLetterEnabled')
            ->willReturn(false);

        $entity->expects($this->never())
            ->method('setExportStatus');

        $this->failureRecorder->recordFailure($entity, 'unknown', 'msg');
    }

    public function testTransientFailureNeverDeadLettersAtOrOverCeiling(): void
    {
        $entity = $this->createMock(FailureTrackableInterface::class);

        // attempts becomes 3 (2 + 1), max=3 -> would FAILED, but transient prevents it.
        $entity->expects($this->any())
            ->method('getExportAttempts')
            ->willReturn(2);

        $entity->expects($this->once())
            ->method('setExportAttempts')
            ->with(3);

        $entity->expects($this->once())
            ->method('setLastErrorCode')
            ->with('http_error');

        $this->config->expects($this->any())
            ->method('getMaxExportAttempts')
            ->willReturn(3);

        $this->config->expects($this->any())
            ->method('isDeadLetterEnabled')
            ->willReturn(true);

        $entity->expects($this->never())
            ->method('setExportStatus');

        $this->failureRecorder->recordFailure($entity, 'http_error', 'boom', true);
    }

    public function testPermanentFailureDeadLettersAtCeiling(): void
    {
        $entity = $this->createMock(FailureTrackableInterface::class);

        // attempts becomes 3 (2 + 1), max=3, permanent (transient=false) -> FAILED.
        $entity->expects($this->any())
            ->method('getExportAttempts')
            ->willReturn(2);

        $this->config->expects($this->any())
            ->method('getMaxExportAttempts')
            ->willReturn(3);

        $this->config->expects($this->any())
            ->method('isDeadLetterEnabled')
            ->willReturn(true);

        $entity->expects($this->once())
            ->method('setExportStatus')
            ->with(FailureTrackableInterface::EXPORT_STATUS_FAILED);

        $this->failureRecorder->recordFailure($entity, 'http_error', 'boom', false);
    }

    public function testTransientFailureStillRecordsAttemptsAndMetadata(): void
    {
        $entity = $this->createMock(FailureTrackableInterface::class);

        $entity->expects($this->any())
            ->method('getExportAttempts')
            ->willReturn(0);

        $entity->expects($this->once())
            ->method('setExportAttempts')
            ->with(1);

        $entity->expects($this->once())
            ->method('setLastErrorCode')
            ->with('http_error');

        $entity->expects($this->once())
            ->method('setLastErrorMessage')
            ->with('boom');

        $entity->expects($this->once())
            ->method('setLastAttemptedAt')
            ->with(self::FIXED_DATE);

        $this->config->expects($this->any())
            ->method('getMaxExportAttempts')
            ->willReturn(0);

        $this->failureRecorder->recordFailure($entity, 'http_error', 'boom', true);
    }

    public function testRecordSuccessSetsSyncedAndResets(): void
    {
        $entity = $this->createMock(FailureTrackableInterface::class);

        $entity->expects($this->once())
            ->method('setExportStatus')
            ->with(FailureTrackableInterface::EXPORT_STATUS_SYNCED);

        $entity->expects($this->once())
            ->method('setExportAttempts')
            ->with(0);

        $entity->expects($this->once())
            ->method('setLastErrorCode')
            ->with(null);

        $entity->expects($this->once())
            ->method('setLastErrorMessage')
            ->with(null);

        $this->failureRecorder->recordSuccess($entity);
    }
}
