<?php
declare(strict_types=1);
/**
 */

namespace CommerceLeague\ActiveCampaign\Console\Command;

use CommerceLeague\ActiveCampaign\Api\Data\FailureTrackableInterface;
use CommerceLeague\ActiveCampaign\Model\ResourceModel\ActiveCampaign\Contact\CollectionFactory as ContactCollectionFactory;
use CommerceLeague\ActiveCampaign\Model\ResourceModel\ActiveCampaign\Customer\CollectionFactory as CustomerCollectionFactory;
use CommerceLeague\ActiveCampaign\Model\ResourceModel\ActiveCampaign\GuestCustomer\CollectionFactory as GuestCustomerCollectionFactory;
use CommerceLeague\ActiveCampaign\Model\ResourceModel\ActiveCampaign\Order\CollectionFactory as OrderCollectionFactory;
use Magento\Framework\Console\Cli;
use Magento\Framework\Data\Collection\AbstractDb;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class ExportStatusCommand
 *
 * Read-only diagnostics: for each of the four AC mapping tables, report total
 * rows, NULL-activecampaign_id count, counts grouped by export_status, and the
 * NULL rows grouped by last_error_code.
 */
class ExportStatusCommand extends Command
{
    private const NAME = 'activecampaign:export:status';

    private const FIELD_ACTIVE_CAMPAIGN_ID = 'activecampaign_id';
    private const FIELD_EXPORT_STATUS      = 'export_status';

    public function __construct(
        private readonly OrderCollectionFactory $orderCollectionFactory,
        private readonly CustomerCollectionFactory $customerCollectionFactory,
        private readonly GuestCustomerCollectionFactory $guestCustomerCollectionFactory,
        private readonly ContactCollectionFactory $contactCollectionFactory
    ) {
        parent::__construct();
    }

    /**
     * @inheritDoc
     */
    protected function configure()
    {
        $this->setName(self::NAME)
            ->setDescription('Report ActiveCampaign export status per mapping table');
    }

    /**
     * @inheritDoc
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $tables = [
            'order'    => $this->orderCollectionFactory,
            'customer' => $this->customerCollectionFactory,
            'guest'    => $this->guestCustomerCollectionFactory,
            'contact'  => $this->contactCollectionFactory,
        ];

        $table = new Table($output);
        $table->setHeaders([
            'Table',
            'Total',
            'NULL ac_id',
            'Pending',
            'Synced',
            'Failed',
            'NULL by last_error_code',
        ]);

        foreach ($tables as $label => $factory) {
            $total   = $this->countAll($factory);
            $null    = $this->countNull($factory);
            $pending = $this->countByStatus($factory, FailureTrackableInterface::EXPORT_STATUS_PENDING);
            $synced  = $this->countByStatus($factory, FailureTrackableInterface::EXPORT_STATUS_SYNCED);
            $failed  = $this->countByStatus($factory, FailureTrackableInterface::EXPORT_STATUS_FAILED);

            $breakdown = $this->nullErrorBreakdown($factory);
            $breakdownLines = [];
            foreach ($breakdown as $code => $count) {
                $breakdownLines[] = sprintf('%s: %d', $code, $count);
            }

            $table->addRow([
                $label,
                $total,
                $null,
                $pending,
                $synced,
                $failed,
                $breakdownLines === [] ? '-' : implode("\n", $breakdownLines),
            ]);
        }

        $table->render();

        return Cli::RETURN_SUCCESS;
    }

    private function countAll(object $factory): int
    {
        /** @var AbstractDb $collection */
        $collection = $factory->create();

        return (int)$collection->getSize();
    }

    private function countNull(object $factory): int
    {
        /** @var AbstractDb $collection */
        $collection = $factory->create();
        $collection->addFieldToFilter(self::FIELD_ACTIVE_CAMPAIGN_ID, ['null' => true]);

        return (int)$collection->getSize();
    }

    private function countByStatus(object $factory, int $status): int
    {
        /** @var AbstractDb $collection */
        $collection = $factory->create();
        $collection->addFieldToFilter(self::FIELD_EXPORT_STATUS, ['eq' => $status]);

        return (int)$collection->getSize();
    }

    /**
     * @return array<string, int>
     */
    private function nullErrorBreakdown(object $factory): array
    {
        /** @var AbstractDb $collection */
        $collection = $factory->create();
        $collection->addFieldToFilter(self::FIELD_ACTIVE_CAMPAIGN_ID, ['null' => true]);

        $breakdown = [];
        foreach ($collection->getItems() as $model) {
            /** @var FailureTrackableInterface $model */
            $code = $model->getLastErrorCode() ?? '(none)';
            $breakdown[$code] = ($breakdown[$code] ?? 0) + 1;
        }

        return $breakdown;
    }
}
