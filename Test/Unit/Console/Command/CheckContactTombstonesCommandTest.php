<?php
declare(strict_types=1);
/**
 * Copyright © André Flitsch. All rights reserved.
 * See license.md for license details.
 */

namespace CommerceLeague\ActiveCampaign\Test\Unit\Console\Command;

use CommerceLeague\ActiveCampaign\Console\Command\CheckContactTombstonesCommand;
use CommerceLeague\ActiveCampaign\Model\Tombstone\ContactTombstoneChecker;
use CommerceLeague\ActiveCampaign\Test\Unit\AbstractTestCase;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\Console\Exception\RuntimeException;
use Symfony\Component\Console\Tester\CommandTester;

class CheckContactTombstonesCommandTest extends AbstractTestCase
{
    /**
     * @var MockObject|ContactTombstoneChecker
     */
    private $checker;

    /**
     * @var CommandTester
     */
    private $commandTester;

    protected function setUp(): void
    {
        $this->checker = $this->createMock(ContactTombstoneChecker::class);

        $command = new CheckContactTombstonesCommand($this->checker);
        $this->commandTester = new CommandTester($command);
    }

    public function testRequiresEmailOrAll(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Please provide --email or --all');

        $this->commandTester->execute([]);
    }

    public function testAllPassesNullEmailAndReportsSummary(): void
    {
        $this->checker->expects($this->once())
            ->method('check')
            ->with(null, 0, $this->isType('callable'))
            ->willReturnCallback(function (?string $email, int $limit, callable $onRecord) {
                $onRecord([
                    'entityId'         => 21328,
                    'email'            => 'jane.doe@example.com',
                    'activeCampaignId' => 105872,
                    'outcome'          => ContactTombstoneChecker::RESULT_FOUND,
                ]);
                $onRecord([
                    'entityId'         => 21602,
                    'email'            => 'john.doe@example.com',
                    'activeCampaignId' => 106578,
                    'outcome'          => ContactTombstoneChecker::RESULT_NOT_FOUND,
                ]);

                return [
                    ContactTombstoneChecker::RESULT_FOUND     => 1,
                    ContactTombstoneChecker::RESULT_NOT_FOUND => 1,
                    ContactTombstoneChecker::RESULT_ERROR     => 0,
                ];
            });

        $this->commandTester->execute(['--all' => true]);

        $display = $this->commandTester->getDisplay();

        $this->assertStringContainsString('found: 1', $display);
        $this->assertStringContainsString('not_found: 1', $display);
        $this->assertStringContainsString(
            'bin/magento activecampaign:export:contact --email john.doe@example.com',
            $display
        );
        $this->assertStringNotContainsString(
            'bin/magento activecampaign:export:contact --email jane.doe@example.com',
            $display
        );
    }

    public function testEmailOptionIsPassedThrough(): void
    {
        $this->checker->expects($this->once())
            ->method('check')
            ->with('jane.doe@example.com', 0, $this->isType('callable'))
            ->willReturn([
                ContactTombstoneChecker::RESULT_FOUND     => 0,
                ContactTombstoneChecker::RESULT_NOT_FOUND => 0,
                ContactTombstoneChecker::RESULT_ERROR     => 0,
            ]);

        $this->commandTester->execute(['--email' => 'jane.doe@example.com']);
    }

    public function testLimitOptionIsPassedThrough(): void
    {
        $this->checker->expects($this->once())
            ->method('check')
            ->with(null, 50, $this->isType('callable'))
            ->willReturn([
                ContactTombstoneChecker::RESULT_FOUND     => 0,
                ContactTombstoneChecker::RESULT_NOT_FOUND => 0,
                ContactTombstoneChecker::RESULT_ERROR     => 0,
            ]);

        $this->commandTester->execute(['--all' => true, '--limit' => 50]);
    }
}
