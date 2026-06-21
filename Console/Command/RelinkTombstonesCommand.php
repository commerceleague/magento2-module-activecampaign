<?php
declare(strict_types=1);
/**
 * Copyright © André Flitsch. All rights reserved.
 * See license.md for license details.
 */

namespace CommerceLeague\ActiveCampaign\Console\Command;

use CommerceLeague\ActiveCampaign\Helper\Config;
use CommerceLeague\ActiveCampaign\Model\ActiveCampaign\Customer;
use CommerceLeague\ActiveCampaign\Model\ActiveCampaign\GuestCustomer;
use CommerceLeague\ActiveCampaign\Model\ResourceModel\ActiveCampaign\Customer\CollectionFactory as CustomerCollectionFactory;
use CommerceLeague\ActiveCampaign\Model\ResourceModel\ActiveCampaign\GuestCustomer\CollectionFactory as GuestCustomerCollectionFactory;
use CommerceLeague\ActiveCampaign\Model\Tombstone\TombstoneReconciler;
use CommerceLeague\ActiveCampaign\Model\Tombstone\TombstoneRelinker;
use Magento\Framework\Console\Cli;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class RelinkTombstonesCommand
 *
 * Operator command that performs the id-preserving relink of tombstoned
 * ecomCustomers (see TombstoneRelinker). DRY-RUN by default: nothing is written
 * to ActiveCampaign unless --commit is passed. Idempotent: a non-tombstone
 * record is never modified.
 */
class RelinkTombstonesCommand extends Command
{
    private const NAME = 'activecampaign:relink:tombstones';

    private const OPTION_MAGENTO_CUSTOMER_ID = 'magento-customer-id';
    private const OPTION_GUEST_ID            = 'guest-id';
    private const OPTION_EMAIL               = 'email';
    private const OPTION_ALL                 = 'all';
    private const OPTION_ONLY_LIVE_CONTACT   = 'only-live-contact';
    private const OPTION_COMMIT              = 'commit';
    private const OPTION_LIMIT               = 'limit';

    private const FIELD_ACTIVE_CAMPAIGN_ID = 'activecampaign_id';

    private const TYPE_REGISTERED = 'registered';
    private const TYPE_GUEST      = 'guest';

    public function __construct(
        private readonly CustomerCollectionFactory $customerCollectionFactory,
        private readonly GuestCustomerCollectionFactory $guestCustomerCollectionFactory,
        private readonly Config $config,
        private readonly TombstoneRelinker $relinker,
        private readonly TombstoneReconciler $reconciler
    ) {
        parent::__construct();
    }

    /**
     * @inheritDoc
     */
    protected function configure(): void
    {
        $this->setName(self::NAME)
            ->setDescription(
                'Relink tombstoned ecomCustomers (restore real email, id-preserving). DRY-RUN by default.'
            )
            ->addOption(
                self::OPTION_MAGENTO_CUSTOMER_ID,
                null,
                InputOption::VALUE_REQUIRED,
                'Single registered target: the Magento customer id of the mapping to relink'
            )
            ->addOption(
                self::OPTION_GUEST_ID,
                null,
                InputOption::VALUE_REQUIRED,
                'Single guest target: the guest mapping entity id to relink'
            )
            ->addOption(
                self::OPTION_EMAIL,
                null,
                InputOption::VALUE_REQUIRED,
                'Single target by email (matches a registered or guest mapping)'
            )
            ->addOption(
                self::OPTION_ALL,
                null,
                InputOption::VALUE_NONE,
                'Batch: process all tombstone-candidate mappings (registered + guest)'
            )
            ->addOption(
                self::OPTION_ONLY_LIVE_CONTACT,
                null,
                InputOption::VALUE_NONE,
                'Documented safe batch default: the relinker skips no-live-contact targets anyway'
            )
            ->addOption(
                self::OPTION_COMMIT,
                null,
                InputOption::VALUE_NONE,
                'Actually write the relink to ActiveCampaign (without it the run is a dry-run)'
            )
            ->addOption(
                self::OPTION_LIMIT,
                null,
                InputOption::VALUE_REQUIRED,
                'Cap the number of mappings processed per type (0 or unset = no cap)',
                0
            );
    }

    /**
     * @inheritDoc
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $commit = (bool)$input->getOption(self::OPTION_COMMIT);
        $limit  = (int)$input->getOption(self::OPTION_LIMIT);

        $magentoCustomerId = $input->getOption(self::OPTION_MAGENTO_CUSTOMER_ID);
        $guestId           = $input->getOption(self::OPTION_GUEST_ID);
        $email             = $input->getOption(self::OPTION_EMAIL);
        $all               = (bool)$input->getOption(self::OPTION_ALL);

        $this->writeHeader($output, $commit);

        $tally = [
            TombstoneRelinker::RESULT_RELINKED              => 0,
            TombstoneRelinker::RESULT_SKIPPED_NOT_TOMBSTONE => 0,
            TombstoneRelinker::RESULT_SKIPPED_CONFLICT      => 0,
            TombstoneRelinker::RESULT_NO_LIVE_CONTACT       => 0,
            TombstoneRelinker::RESULT_NOT_FOUND             => 0,
        ];
        $unresolved = 0;

        foreach ($this->collectTargets($all, $magentoCustomerId, $guestId, $email, $limit) as $target) {
            $resolved = $this->resolveTarget($target, $output);

            if ($resolved === null) {
                $unresolved++;
                continue;
            }

            [$ecomCustomerId, $realEmail, $externalId, $type] = $resolved;

            // A registered mapping's real email is only known after repository
            // resolution, so honour the --email selector here too. Emails are
            // case-insensitive (the TombstoneRelinker compares with strtolower),
            // so match case-insensitively.
            if ($email !== null && strtolower($realEmail) !== strtolower((string)$email)) {
                continue;
            }

            $outcome = $this->relinker->relink($ecomCustomerId, $realEmail, $externalId, $commit);
            $tally[$outcome] = ($tally[$outcome] ?? 0) + 1;

            $output->writeln(sprintf(
                '  ecomId=%d type=%s email=%s -> %s',
                $ecomCustomerId,
                $type,
                $realEmail,
                $outcome
            ));
        }

        $this->writeSummary($output, $tally, $unresolved, $commit);

        return Cli::RETURN_SUCCESS;
    }

    /**
     * Build the target set as raw mapping models (registered + guest).
     *
     * @return array<int, array{0:string,1:\Magento\Framework\Model\AbstractModel}>
     */
    private function collectTargets(
        bool $all,
        mixed $magentoCustomerId,
        mixed $guestId,
        mixed $email,
        int $limit
    ): array {
        $targets = [];

        $wantRegistered = $all || $magentoCustomerId !== null || $email !== null;
        $wantGuest      = $all || $guestId !== null || $email !== null;

        if ($wantRegistered) {
            $collection = $this->customerCollectionFactory->create();
            $collection->addFieldToFilter(self::FIELD_ACTIVE_CAMPAIGN_ID, ['notnull' => true]);

            if ($magentoCustomerId !== null) {
                $collection->addFieldToFilter('magento_customer_id', (string)(int)$magentoCustomerId);
            }

            if ($limit > 0) {
                $collection->setPageSize($limit);
            }

            /** @var Customer $model */
            foreach ($collection->getItems() as $model) {
                // Belt-and-braces narrowing for the single-target selector so we
                // never relink the wrong row even if the collection was not
                // (or could not be) filtered at the DB level.
                if ($magentoCustomerId !== null
                    && (int)$model->getMagentoCustomerId() !== (int)$magentoCustomerId
                ) {
                    continue;
                }

                $targets[] = [self::TYPE_REGISTERED, $model];
            }
        }

        if ($wantGuest) {
            $collection = $this->guestCustomerCollectionFactory->create();
            $collection->addFieldToFilter(self::FIELD_ACTIVE_CAMPAIGN_ID, ['notnull' => true]);

            if ($guestId !== null) {
                // Explicitly qualified: sales_order is LEFT JOINed in the guest
                // collection and also exposes entity_id, so a bare filter is
                // ambiguous (SQLSTATE[23000]).
                $collection->addEntityIdFilter((int)$guestId);
            }

            if ($email !== null) {
                // Qualify as main_table.email; sales_order.customer_email in the
                // join would otherwise make a bare email filter unsafe.
                $collection->addEmailFilter((string)$email);
            }

            if ($limit > 0) {
                $collection->setPageSize($limit);
            }

            /** @var GuestCustomer $model */
            foreach ($collection->getItems() as $model) {
                if ($guestId !== null && (int)$model->getId() !== (int)$guestId) {
                    continue;
                }

                if ($email !== null
                    && strtolower((string)$model->getEmail()) !== strtolower((string)$email)
                ) {
                    continue;
                }

                $targets[] = [self::TYPE_GUEST, $model];
            }
        }

        return $targets;
    }

    /**
     * Resolve a raw mapping into (ecomCustomerId, realEmail, externalId, type).
     *
     * Returns null when the data cannot be resolved (e.g. a registered mapping
     * whose Magento customer record was itself deleted, or an --email filter
     * that does not match a registered mapping's real email).
     *
     * @param array{0:string,1:\Magento\Framework\Model\AbstractModel} $target
     * @return array{0:int,1:string,2:string,3:string}|null
     */
    private function resolveTarget(array $target, OutputInterface $output): ?array
    {
        // Delegate the resolution rules to the shared reconciler so there is a
        // single source of truth for (ecomCustomerId, realEmail, externalId,
        // type). The command keeps its own per-record narrowing (--email) and
        // operator output around this call.
        $resolved = $this->reconciler->resolveCandidate($target);

        if ($resolved === null) {
            [, $model] = $target;
            /** @var Customer $model */
            $output->writeln(sprintf(
                '  <comment>skip unresolved registered mapping: magento_customer_id=%d</comment>',
                (int)$model->getMagentoCustomerId()
            ));
        }

        return $resolved;
    }

    private function writeHeader(OutputInterface $output, bool $commit): void
    {
        if ($commit) {
            $output->writeln('<info>Tombstone relink: COMMIT mode (writes will be sent to ActiveCampaign).</info>');
        } else {
            $output->writeln('<comment>Tombstone relink: DRY-RUN (no writes). Pass --commit to apply.</comment>');
        }

        $output->writeln(sprintf('AC connection id: %s', (string)$this->config->getConnectionId()));
        $output->writeln('');
    }

    /**
     * @param array<string, int> $tally
     */
    private function writeSummary(OutputInterface $output, array $tally, int $unresolved, bool $commit): void
    {
        $output->writeln('');
        $output->writeln('<comment>Summary</comment>');
        $output->writeln(sprintf('  relinked: %d', $tally[TombstoneRelinker::RESULT_RELINKED]));
        $output->writeln(sprintf('  skipped_not_tombstone: %d', $tally[TombstoneRelinker::RESULT_SKIPPED_NOT_TOMBSTONE]));
        $output->writeln(sprintf('  skipped_conflict: %d', $tally[TombstoneRelinker::RESULT_SKIPPED_CONFLICT]));
        $output->writeln(sprintf('  no_live_contact: %d', $tally[TombstoneRelinker::RESULT_NO_LIVE_CONTACT]));
        $output->writeln(sprintf('  not_found: %d', $tally[TombstoneRelinker::RESULT_NOT_FOUND]));
        $output->writeln(sprintf('  unresolved (skipped, no email): %d', $unresolved));

        $output->writeln('');

        if ($commit) {
            $output->writeln('<info>COMMIT run complete.</info>');
        } else {
            $output->writeln('<comment>DRY-RUN complete. Nothing was written. Re-run with --commit to apply.</comment>');
        }
    }
}
