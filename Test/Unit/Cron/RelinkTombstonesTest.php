<?php
declare(strict_types=1);
/**
 * Copyright © André Flitsch. All rights reserved.
 * See license.md for license details.
 */

namespace CommerceLeague\ActiveCampaign\Test\Unit\Cron;

use CommerceLeague\ActiveCampaign\Cron\RelinkTombstones;
use CommerceLeague\ActiveCampaign\Helper\Config as ConfigHelper;
use CommerceLeague\ActiveCampaign\Model\Tombstone\TombstoneReconciler;
use CommerceLeague\ActiveCampaign\Model\Tombstone\TombstoneRelinker;
use CommerceLeague\ActiveCampaign\Test\Unit\AbstractTestCase;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;

class RelinkTombstonesTest extends AbstractTestCase
{
    /**
     * @var MockObject|ConfigHelper
     */
    private $configHelper;

    /**
     * @var MockObject|TombstoneReconciler
     */
    private $reconciler;

    /**
     * @var MockObject|LoggerInterface
     */
    private $logger;

    /**
     * @var RelinkTombstones
     */
    private $cron;

    protected function setUp(): void
    {
        $this->configHelper = $this->createMock(ConfigHelper::class);
        $this->reconciler   = $this->createMock(TombstoneReconciler::class);
        $this->logger       = $this->createMock(LoggerInterface::class);

        $this->cron = new RelinkTombstones(
            $this->configHelper,
            $this->reconciler,
            $this->logger
        );
    }

    public function testRunDisabledDoesNothing(): void
    {
        $this->configHelper->expects($this->once())
            ->method('isRelinkCronEnabled')
            ->willReturn(false);

        $this->reconciler->expects($this->never())->method('reconcile');
        $this->logger->expects($this->never())->method('info');

        $this->cron->run();
    }

    public function testRunEnabledDelegatesToReconcilerWithCommitTrueAndLogsSummary(): void
    {
        $this->configHelper->expects($this->once())
            ->method('isRelinkCronEnabled')
            ->willReturn(true);

        $tally = [
            TombstoneRelinker::RESULT_RELINKED              => 3,
            TombstoneRelinker::RESULT_SKIPPED_NOT_TOMBSTONE => 1,
            TombstoneRelinker::RESULT_SKIPPED_CONFLICT      => 0,
            TombstoneRelinker::RESULT_NO_LIVE_CONTACT       => 2,
            TombstoneRelinker::RESULT_NOT_FOUND             => 0,
            'unresolved'                                    => 1,
            'error'                                         => 0,
        ];

        $this->reconciler->expects($this->once())
            ->method('reconcile')
            ->with(true, $this->isType('callable'))
            ->willReturn($tally);

        $this->logger->expects($this->once())
            ->method('info')
            ->with($this->stringContains('relinked'));

        $this->cron->run();
    }
}
