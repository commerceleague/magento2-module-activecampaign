<?php
declare(strict_types=1);
/**
 */

namespace CommerceLeague\ActiveCampaign\Console\Command;

use CommerceLeague\ActiveCampaign\Api\Data\ContactInterface;
use CommerceLeague\ActiveCampaign\Api\Data\CustomerInterface;
use CommerceLeague\ActiveCampaign\Api\Data\FailureTrackableInterface;
use CommerceLeague\ActiveCampaign\Api\Data\GuestCustomerInterface;
use CommerceLeague\ActiveCampaign\Api\Data\OrderInterface;
use CommerceLeague\ActiveCampaign\MessageQueue\Topics;
use CommerceLeague\ActiveCampaign\Model\ActiveCampaign\Contact;
use CommerceLeague\ActiveCampaign\Model\ActiveCampaign\Customer;
use CommerceLeague\ActiveCampaign\Model\ActiveCampaign\GuestCustomer;
use CommerceLeague\ActiveCampaign\Model\ActiveCampaign\Order;
use CommerceLeague\ActiveCampaign\Model\ResourceModel\ActiveCampaign\Contact as ContactResource;
use CommerceLeague\ActiveCampaign\Model\ResourceModel\ActiveCampaign\Contact\CollectionFactory as ContactCollectionFactory;
use CommerceLeague\ActiveCampaign\Model\ResourceModel\ActiveCampaign\Customer as CustomerResource;
use CommerceLeague\ActiveCampaign\Model\ResourceModel\ActiveCampaign\Customer\CollectionFactory as CustomerCollectionFactory;
use CommerceLeague\ActiveCampaign\Model\ResourceModel\ActiveCampaign\GuestCustomer as GuestCustomerResource;
use CommerceLeague\ActiveCampaign\Model\ResourceModel\ActiveCampaign\GuestCustomer\CollectionFactory as GuestCustomerCollectionFactory;
use CommerceLeague\ActiveCampaign\Model\ResourceModel\ActiveCampaign\Order as OrderResource;
use CommerceLeague\ActiveCampaign\Model\ResourceModel\ActiveCampaign\Order\CollectionFactory as OrderCollectionFactory;
use Magento\Framework\Console\Cli;
use Magento\Framework\MessageQueue\PublisherInterface;
use Magento\Framework\Model\AbstractModel;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class BackfillMissingIdsCommand
 *
 * Re-processes all mapping rows whose activecampaign_id IS NULL, ignoring the
 * date/status/group window filters, by re-publishing the appropriate export
 * topic per type so the existing consumers can fill the id.
 */
class BackfillMissingIdsCommand extends Command
{
    private const NAME = 'activecampaign:export:backfill';

    private const OPTION_TYPE              = 'type';
    private const OPTION_LIMIT             = 'limit';
    private const OPTION_DRY_RUN           = 'dry-run';
    private const OPTION_INCLUDE_FAILED    = 'include-failed';
    private const OPTION_FLAG_UNRECOVERABLE = 'flag-unrecoverable';

    private const TYPE_CONTACT  = 'contact';
    private const TYPE_CUSTOMER = 'customer';
    private const TYPE_GUEST    = 'guest';
    private const TYPE_ORDER    = 'order';
    private const TYPE_ALL      = 'all';

    private const FIELD_ACTIVE_CAMPAIGN_ID = 'activecampaign_id';
    private const FIELD_EXPORT_STATUS      = 'export_status';

    /**
     * Error codes that will never succeed on retry; rows carrying them are
     * candidates for flagging as permanently failed instead of re-queuing.
     */
    private const UNRECOVERABLE_ERROR_CODES = ['email_invalid', 'field_missing'];

    public function __construct(
        private readonly OrderCollectionFactory $orderCollectionFactory,
        private readonly CustomerCollectionFactory $customerCollectionFactory,
        private readonly GuestCustomerCollectionFactory $guestCustomerCollectionFactory,
        private readonly ContactCollectionFactory $contactCollectionFactory,
        private readonly PublisherInterface $publisher,
        private readonly OrderResource $orderResource,
        private readonly CustomerResource $customerResource,
        private readonly GuestCustomerResource $guestCustomerResource,
        private readonly ContactResource $contactResource
    ) {
        parent::__construct();
    }

    /**
     * @inheritDoc
     */
    protected function configure(): void
    {
        $this->setName(self::NAME)
            ->setDescription('Backfill missing ActiveCampaign ids by re-publishing export topics')
            ->addOption(
                self::OPTION_TYPE,
                null,
                InputOption::VALUE_REQUIRED,
                'Entity type to backfill: contact|customer|guest|order|all',
                self::TYPE_ALL
            )
            ->addOption(
                self::OPTION_LIMIT,
                null,
                InputOption::VALUE_REQUIRED,
                'Cap the number of rows processed per type (0 or unset = no cap)',
                0
            )
            ->addOption(
                self::OPTION_DRY_RUN,
                null,
                InputOption::VALUE_NONE,
                'Count and report only; publish and save nothing'
            )
            ->addOption(
                self::OPTION_INCLUDE_FAILED,
                null,
                InputOption::VALUE_NONE,
                'Also process rows with export_status = failed (excluded by default)'
            )
            ->addOption(
                self::OPTION_FLAG_UNRECOVERABLE,
                null,
                InputOption::VALUE_NONE,
                'Flag rows with a known-unrecoverable last_error_code as failed instead of re-queuing'
            );
    }

    /**
     * @inheritDoc
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $type             = (string)$input->getOption(self::OPTION_TYPE);
        $limit            = (int)$input->getOption(self::OPTION_LIMIT);
        $dryRun           = (bool)$input->getOption(self::OPTION_DRY_RUN);
        $includeFailed    = (bool)$input->getOption(self::OPTION_INCLUDE_FAILED);
        $flagUnrecoverable = (bool)$input->getOption(self::OPTION_FLAG_UNRECOVERABLE);

        $types = $type === self::TYPE_ALL
            ? [self::TYPE_ORDER, self::TYPE_CUSTOMER, self::TYPE_GUEST, self::TYPE_CONTACT]
            : [$type];

        $totalRequeued = 0;
        $totalFlagged  = 0;

        foreach ($types as $currentType) {
            [$requeued, $flagged, $errorBreakdown] = $this->processType(
                $currentType,
                $limit,
                $dryRun,
                $includeFailed,
                $flagUnrecoverable
            );

            $totalRequeued += $requeued;
            $totalFlagged  += $flagged;

            $this->reportType($output, $currentType, $requeued, $flagged, $errorBreakdown);
        }

        $output->writeln('');
        $output->writeln(sprintf(
            '<info>Overall: %d re-queued, %d flagged-unrecoverable%s.</info>',
            $totalRequeued,
            $totalFlagged,
            $dryRun ? ' (dry-run, nothing published or saved)' : ''
        ));

        return Cli::RETURN_SUCCESS;
    }

    /**
     * @return array{0:int,1:int,2:array<string,int>}
     */
    private function processType(
        string $type,
        int $limit,
        bool $dryRun,
        bool $includeFailed,
        bool $flagUnrecoverable
    ): array {
        $collection = $this->createCollection($type);
        $collection->addFieldToFilter(self::FIELD_ACTIVE_CAMPAIGN_ID, ['null' => true]);

        if (!$includeFailed) {
            $collection->addFieldToFilter(
                self::FIELD_EXPORT_STATUS,
                ['neq' => FailureTrackableInterface::EXPORT_STATUS_FAILED]
            );
        }

        if ($limit > 0) {
            $collection->setPageSize($limit);
        }

        $requeued       = 0;
        $flagged        = 0;
        $errorBreakdown = [];

        /** @var AbstractModel $model */
        foreach ($collection->getItems() as $model) {
            $errorCode = $model->getLastErrorCode();
            $key       = $errorCode ?? '(none)';
            $errorBreakdown[$key] = ($errorBreakdown[$key] ?? 0) + 1;

            if ($flagUnrecoverable
                && $errorCode !== null
                && in_array($errorCode, self::UNRECOVERABLE_ERROR_CODES, true)
            ) {
                if (!$dryRun) {
                    $this->flag($type, $model);
                }
                $flagged++;
                continue;
            }

            if (!$dryRun) {
                $this->publishFor($type, $model);
            }
            $requeued++;
        }

        return [$requeued, $flagged, $errorBreakdown];
    }

    private function createCollection(string $type): \Magento\Framework\Data\Collection\AbstractDb
    {
        return match ($type) {
            self::TYPE_ORDER    => $this->orderCollectionFactory->create(),
            self::TYPE_CUSTOMER => $this->customerCollectionFactory->create(),
            self::TYPE_GUEST    => $this->guestCustomerCollectionFactory->create(),
            self::TYPE_CONTACT  => $this->contactCollectionFactory->create(),
            default             => throw new \InvalidArgumentException(sprintf('Unknown type "%s"', $type)),
        };
    }

    private function flag(string $type, AbstractModel $model): void
    {
        $model->setExportStatus(FailureTrackableInterface::EXPORT_STATUS_FAILED);

        match ($type) {
            self::TYPE_ORDER    => $this->orderResource->save($model),
            self::TYPE_CUSTOMER => $this->customerResource->save($model),
            self::TYPE_GUEST    => $this->guestCustomerResource->save($model),
            self::TYPE_CONTACT  => $this->contactResource->save($model),
            default             => null,
        };
    }

    private function publishFor(string $type, AbstractModel $model): void
    {
        switch ($type) {
            case self::TYPE_ORDER:
                /** @var Order $model */
                if ($model->getMagentoOrderId() !== null) {
                    $this->publish(
                        Topics::SALES_ORDER_EXPORT,
                        [OrderInterface::MAGENTO_ORDER_ID => (int)$model->getMagentoOrderId()]
                    );
                } elseif ($model->getMagentoQuoteId() !== null) {
                    $this->publish(
                        Topics::QUOTE_ABANDONED_CART_EXPORT,
                        ['quote_id' => (int)$model->getMagentoQuoteId()]
                    );
                }
                break;
            case self::TYPE_CUSTOMER:
                /** @var Customer $model */
                $this->publish(
                    Topics::CUSTOMER_CUSTOMER_EXPORT,
                    [CustomerInterface::MAGENTO_CUSTOMER_ID => (int)$model->getMagentoCustomerId()]
                );
                break;
            case self::TYPE_GUEST:
                /** @var GuestCustomer $model */
                $this->publish(
                    Topics::GUEST_CUSTOMER_EXPORT,
                    [
                        'magento_customer_id' => null,
                        'customer_is_guest'   => true,
                        'customer_data'       => [
                            GuestCustomerInterface::FIRSTNAME => $model->getFirstname(),
                            GuestCustomerInterface::LASTNAME  => $model->getLastname(),
                            GuestCustomerInterface::EMAIL     => $model->getEmail(),
                        ],
                    ]
                );
                break;
            case self::TYPE_CONTACT:
                /** @var Contact $model */
                $this->publish(
                    Topics::NEWSLETTER_CONTACT_EXPORT,
                    [ContactInterface::EMAIL => $model->getEmail()]
                );
                break;
        }
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function publish(string $topic, array $payload): void
    {
        $this->publisher->publish($topic, json_encode($payload, JSON_THROW_ON_ERROR));
    }

    /**
     * @param array<string, int> $errorBreakdown
     */
    private function reportType(
        OutputInterface $output,
        string $type,
        int $requeued,
        int $flagged,
        array $errorBreakdown
    ): void {
        $output->writeln('');
        $output->writeln(sprintf('<comment>%s</comment>', ucfirst($type)));
        $output->writeln(sprintf('  re-queued: %d', $requeued));
        $output->writeln(sprintf('  flagged-unrecoverable: %d', $flagged));

        if ($errorBreakdown !== []) {
            $output->writeln('  NULL rows by last_error_code:');
            foreach ($errorBreakdown as $code => $count) {
                $output->writeln(sprintf('    %s: %d', $code, $count));
            }
        }
    }
}
