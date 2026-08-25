<?php
declare(strict_types=1);
/**
 * Copyright © André Flitsch. All rights reserved.
 * See license.md for license details.
 */

namespace CommerceLeague\ActiveCampaign\Console\Command;

use CommerceLeague\ActiveCampaign\Model\Tombstone\ContactTombstoneChecker;
use Magento\Framework\Console\Cli;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Exception\RuntimeException;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class CheckContactTombstonesCommand
 *
 * Operator command that audits activecampaign_contact rows against AC. Always
 * read-only — it never writes to AC or to the local table. A row reported
 * not_found has a Contact that no longer exists in AC (see
 * {@see ContactTombstoneChecker} for why this differs from a Customer/
 * GuestCustomer tombstone); re-attach it with
 * `activecampaign:export:contact --email <email>`.
 */
class CheckContactTombstonesCommand extends Command
{
    private const NAME = 'activecampaign:contacts:check-tombstones';

    private const OPTION_EMAIL = 'email';
    private const OPTION_ALL   = 'all';
    private const OPTION_LIMIT = 'limit';

    public function __construct(private readonly ContactTombstoneChecker $checker)
    {
        parent::__construct();
    }

    /**
     * @inheritDoc
     */
    protected function configure(): void
    {
        $this->setName(self::NAME)
            ->setDescription(
                'Audit synced activecampaign_contact rows against AC. Read-only: reports gone contacts, writes nothing.'
            )
            ->addOption(
                self::OPTION_EMAIL,
                null,
                InputOption::VALUE_REQUIRED,
                'Check a single contact by email'
            )
            ->addOption(
                self::OPTION_ALL,
                null,
                InputOption::VALUE_NONE,
                'Check every synced contact'
            )
            ->addOption(
                self::OPTION_LIMIT,
                null,
                InputOption::VALUE_REQUIRED,
                'Cap the number of rows checked (0 or unset = no cap)',
                0
            );
    }

    /**
     * @inheritDoc
     */
    protected function interact(InputInterface $input, OutputInterface $output): void
    {
        if ($input->getOption(self::OPTION_EMAIL) === null && $input->getOption(self::OPTION_ALL) === false) {
            throw new RuntimeException('Please provide --email or --all');
        }
    }

    /**
     * @inheritDoc
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $email = $input->getOption(self::OPTION_EMAIL);
        $limit = (int)$input->getOption(self::OPTION_LIMIT);

        $notFound = [];

        $tally = $this->checker->check($email, $limit, function (array $record) use ($output, &$notFound): void {
            $output->writeln(sprintf(
                '  entity_id=%d email=%s activecampaign_id=%d -> %s%s',
                $record['entityId'],
                $record['email'],
                $record['activeCampaignId'],
                $record['outcome'],
                isset($record['error']) ? sprintf(' (%s)', $record['error']) : ''
            ));

            if ($record['outcome'] === ContactTombstoneChecker::RESULT_NOT_FOUND) {
                $notFound[] = $record;
            }
        });

        $output->writeln('');
        $output->writeln('<comment>Summary</comment>');
        $output->writeln(sprintf('  found: %d', $tally[ContactTombstoneChecker::RESULT_FOUND]));
        $output->writeln(sprintf('  not_found: %d', $tally[ContactTombstoneChecker::RESULT_NOT_FOUND]));
        $output->writeln(sprintf('  error: %d', $tally[ContactTombstoneChecker::RESULT_ERROR]));

        if ($notFound !== []) {
            $output->writeln('');
            $output->writeln('<comment>Gone from AC — re-export to reattach:</comment>');
            foreach ($notFound as $record) {
                $output->writeln(sprintf(
                    '  bin/magento activecampaign:export:contact --email %s',
                    $record['email']
                ));
            }
        }

        return Cli::RETURN_SUCCESS;
    }
}
