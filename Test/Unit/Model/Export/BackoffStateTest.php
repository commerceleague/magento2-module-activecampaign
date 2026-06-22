<?php
declare(strict_types=1);
/**
 */

namespace CommerceLeague\ActiveCampaign\Test\Unit\Model\Export;

use CommerceLeague\ActiveCampaign\Helper\Config;
use CommerceLeague\ActiveCampaign\Model\Export\BackoffState;
use CommerceLeague\ActiveCampaign\Test\Unit\AbstractTestCase;
use PHPUnit\Framework\MockObject\MockObject;

class BackoffStateTest extends AbstractTestCase
{
    /**
     * @var MockObject|Config
     */
    private $config;

    /**
     * @var BackoffState
     */
    private $backoffState;

    protected function setUp(): void
    {
        $this->config = $this->createMock(Config::class);
        $this->backoffState = new BackoffState($this->config);
    }

    public function testShouldHaltFalseWhenBackoffDisabledRegardlessOfCount(): void
    {
        $this->config->expects($this->any())
            ->method('isBackoffEnabled')
            ->willReturn(false);

        $this->config->expects($this->any())
            ->method('getBackoffThreshold')
            ->willReturn(3);

        for ($i = 0; $i < 10; $i++) {
            $this->backoffState->record503();
        }

        $this->assertFalse($this->backoffState->shouldHalt());
    }

    public function testShouldHaltFalseBelowThresholdWhenEnabled(): void
    {
        $this->config->expects($this->any())
            ->method('isBackoffEnabled')
            ->willReturn(true);

        $this->config->expects($this->any())
            ->method('getBackoffThreshold')
            ->willReturn(3);

        $this->backoffState->record503();
        $this->backoffState->record503();

        $this->assertFalse($this->backoffState->shouldHalt());
    }

    public function testShouldHaltTrueAtThresholdWhenEnabled(): void
    {
        $this->config->expects($this->any())
            ->method('isBackoffEnabled')
            ->willReturn(true);

        $this->config->expects($this->any())
            ->method('getBackoffThreshold')
            ->willReturn(3);

        $this->backoffState->record503();
        $this->backoffState->record503();
        $this->backoffState->record503();

        $this->assertTrue($this->backoffState->shouldHalt());
    }

    public function testShouldHaltTrueAboveThresholdWhenEnabled(): void
    {
        $this->config->expects($this->any())
            ->method('isBackoffEnabled')
            ->willReturn(true);

        $this->config->expects($this->any())
            ->method('getBackoffThreshold')
            ->willReturn(3);

        for ($i = 0; $i < 5; $i++) {
            $this->backoffState->record503();
        }

        $this->assertTrue($this->backoffState->shouldHalt());
    }

    public function testResetClearsCounter(): void
    {
        $this->config->expects($this->any())
            ->method('isBackoffEnabled')
            ->willReturn(true);

        $this->config->expects($this->any())
            ->method('getBackoffThreshold')
            ->willReturn(3);

        $this->backoffState->record503();
        $this->backoffState->record503();
        $this->backoffState->record503();
        $this->assertTrue($this->backoffState->shouldHalt());

        $this->backoffState->reset();
        $this->assertFalse($this->backoffState->shouldHalt());
    }

    public function testRecord503Increments(): void
    {
        $this->config->expects($this->any())
            ->method('isBackoffEnabled')
            ->willReturn(true);

        $this->config->expects($this->any())
            ->method('getBackoffThreshold')
            ->willReturn(2);

        $this->assertFalse($this->backoffState->shouldHalt());
        $this->backoffState->record503();
        $this->assertFalse($this->backoffState->shouldHalt());
        $this->backoffState->record503();
        $this->assertTrue($this->backoffState->shouldHalt());
    }
}
