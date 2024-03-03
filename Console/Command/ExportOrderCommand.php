<?php
declare(strict_types=1);
/**
 */

namespace CommerceLeague\ActiveCampaign\Console\Command;

use CommerceLeague\ActiveCampaign\Helper\Config as ConfigHelper;
use CommerceLeague\ActiveCampaign\MessageQueue\Topics;
use CommerceLeague\ActiveCampaign\Model\ResourceModel\Order\CollectionFactory as OrderCollectionFactory;
use CommerceLeague\ActiveCampaign\vendor\Model\ResourceModel\Order\Collection as OrderCollection;
use Magento\Framework\Console\Cli;
use Magento\Framework\MessageQueue\PublisherInterface;
use Symfony\Component\Console\Exception\RuntimeException;
use Symfony\Component\Console\Helper\ProgressBarFactory;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class ExportOrderCommand
 */
class ExportOrderCommand extends AbstractExportCommand
{

    private const NAME           = 'activecampaign:export:order';
    private const ORDER_ID       = 'order-id';
    private const OPTION_OMITTED = 'omitted';
    private const OPTION_ALL     = 'all';

    /**
     * @param ConfigHelper           $configHelper
     * @param OrderCollectionFactory $orderCollectionFactory
     * @param ProgressBarFactory     $progressBarFactory
     * @param PublisherInterface     $publisher
     */
    public function __construct(
        ConfigHelper $configHelper,
        private readonly OrderCollectionFactory $orderCollectionFactory,
        ProgressBarFactory $progressBarFactory,
        PublisherInterface $publisher
    ) {
        parent::__construct($configHelper, $progressBarFactory, $publisher);
    }

    /**
     * @return array
     */
    public function getOrderIds(InputInterface $input): array
    {
        /** @var OrderCollection $orderCollection */
        $orderCollection = $this->orderCollectionFactory->create();
//        $orderCollection->addExcludeGuestFilter();

        if (($orderId = $input->getOption(self::ORDER_ID))) {
            $orderCollection->addIdFilter((int)$orderId);
        }

        if ($input->getOption(self::OPTION_OMITTED)) {
            $orderCollection->addOmittedFilter();
        }
        $orderCollection->addExportFilterOrderStatus();
        $orderCollection->addExportFilterStartDate();

        return $orderCollection->getAllIds();
    }

    /**
     * @inheritDoc
     */
    protected function configure()
    {
        $this->setName(self::NAME)
            ->setDescription('Export orders')
            ->addOption(
                self::ORDER_ID,
                null,
                InputOption::VALUE_REQUIRED,
                'The order id'
            )
            ->addOption(
                self::OPTION_OMITTED,
                null,
                InputOption::VALUE_NONE,
                'Only export omitted orders'
            )
            ->addOption(
                self::OPTION_ALL,
                null,
                InputOption::VALUE_NONE,
                'Export all orders'
            );
    }

    /**
     * @inheritDoc
     */
    protected function interact(InputInterface $input, OutputInterface $output)
    {
        if (!$this->configHelper->isEnabled() || !$this->configHelper->isOrderExportEnabled()) {
            throw new RuntimeException('Export disabled by system configuration');
        }

        $orderId = $input->getOption(self::ORDER_ID);
        $omitted = $input->getOption(self::OPTION_OMITTED);
        $all     = $input->getOption(self::OPTION_ALL);

        if ($orderId === null && $omitted === false && $all === false) {
            throw new RuntimeException('Please provide at least one option');
        }

        if ($orderId !== null && ($omitted === true || $all === true)) {
            throw new RuntimeException('You cannot use --order-id together with another option');
        }

        if ($omitted === true && $all === true) {
            throw new RuntimeException('You cannot use --omitted and --all together');
        }
    }

    /**
     * @inheritDoc
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $orderIds      = $this->getOrderIds($input);
        $orderIdsCount = count($orderIds);

        if ($orderIdsCount === 0) {
            $output->writeln('<error>No order(s) found matching your criteria</error>');
            return Cli::RETURN_FAILURE;
        }

        $progressBar = $this->createProgressBar(
            $output,
            $orderIdsCount,
            'Order(s)'
        );

        foreach ($orderIds as $orderId) {
            $this->publisher->publish(
                Topics::SALES_ORDER_EXPORT,
                json_encode(['magento_order_id' => $orderId], JSON_THROW_ON_ERROR)
            );

            $progressBar->advance();
        }

        $output->writeln('');
        $output->writeln(
            sprintf(
                '<info>%s order(s) have been scheduled for export.</info>',
                ($orderIdsCount)
            )
        );

        return Cli::RETURN_SUCCESS;
    }
}
