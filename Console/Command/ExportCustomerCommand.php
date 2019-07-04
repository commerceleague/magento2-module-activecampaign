<?php
declare(strict_types=1);
/**
 */

namespace CommerceLeague\ActiveCampaign\Console\Command;

use CommerceLeague\ActiveCampaign\Service\ExportCustomerService;
use Magento\Customer\Model\Customer;
use Magento\Customer\Model\MagentoCustomerFactory;
use Magento\Customer\Model\ResourceModel\Customer\CollectionFactory as MagentoCustomerCollectionFactory;
use Magento\Customer\Model\ResourceModel\Customer\Collection as MagentoCustomerCollection;
use Magento\Framework\Console\Cli;
use Magento\Framework\Model\ResourceModel\Iterator as ResourceIterator;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProgressBarFactory;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class ExportContactCommand
 */
class ExportCustomerCommand extends Command
{
    private const NAME = 'activecampaign:export:customer';
    private const ARGUMENT_NAME_MAGENTO_CUSTOMER_ID = 'magento-customer-id';
    private const OPTION_NAME_ALL = 'all';

    /**
     * @var MagentoCustomerCollectionFactory
     */
    private $magentoCustomerCollectionFactory;

    /**
     * @var MagentoCustomerFactory
     */
    private $magentoCustomerFactory;

    /**
     * @var ProgressBarFactory
     */
    private $progressBarFactory;

    /**
     * @var ResourceIterator
     */
    private $resourceIterator;

    /**
     * @var ExportCustomerService
     */
    private $exportCustomerService;

    /**
     * @var int
     */
    private $exportMessages = 0;

    /**
     * @param MagentoCustomerCollectionFactory $magentoCustomerCollectionFactory
     * @param MagentoCustomerFactory $magentoCustomerFactory
     * @param ProgressBarFactory $progressBarFactory
     * @param ResourceIterator $resourceIterator
     * @param ExportCustomerService $exportCustomerService
     */
    public function __construct(
        MagentoCustomerCollectionFactory $magentoCustomerCollectionFactory,
        MagentoCustomerFactory $magentoCustomerFactory,
        ProgressBarFactory $progressBarFactory,
        ResourceIterator $resourceIterator,
        ExportCustomerService $exportCustomerService
    ) {
        $this->magentoCustomerCollectionFactory = $magentoCustomerCollectionFactory;
        $this->magentoCustomerFactory = $magentoCustomerFactory;
        $this->progressBarFactory = $progressBarFactory;
        $this->resourceIterator = $resourceIterator;
        $this->exportCustomerService = $exportCustomerService;
        parent::__construct();
    }

    /**
     * @inheritDoc
     */
    protected function configure()
    {
        $this->setName(self::NAME)
            ->addArgument(
                self::ARGUMENT_NAME_MAGENTO_CUSTOMER_ID,
                InputArgument::OPTIONAL
            )->addOption(
                self::OPTION_NAME_ALL,
                null,
                InputOption::VALUE_NONE
            );
    }

    /**
     * @inheritDoc
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $magentoCustomerId = $input->getArgument(self::ARGUMENT_NAME_MAGENTO_CUSTOMER_ID);
        $allMagentoCustomers = $input->getOption(self::OPTION_NAME_ALL);

        if (!$magentoCustomerId && !$allMagentoCustomers) {
            $output->writeln('Either specify the magentoCustomerId or set the --all flag');
            return Cli::RETURN_FAILURE;
        }

        /** @var MagentoCustomerCollection $magentoCustomerCollection */
        $magentoCustomerCollection = $this->magentoCustomerCollectionFactory->create();

        if ($magentoCustomerId) {
            $magentoCustomerCollection->addFieldToFilter('entity_id', $magentoCustomerId);
        }

        if ($magentoCustomerCollection->getSize() === 0) {
            $output->writeln('<error>No valid magento customers found</error>');
            return Cli::RETURN_FAILURE;
        }

        /** @var ProgressBar $progressBar */
        $progressBar = $this->progressBarFactory->create(['output' => $output]);
        $progressBar->setFormat('<comment>%message%</comment> %current% [%bar%] %elapsed%');
        $progressBar->setMessage('Export Message(s)');

        $this->resourceIterator->walk(
            $magentoCustomerCollection->getSelect(),
            [[$this, 'callbackExportCustomer']],
            [
                'magentoCustomer' => $this->magentoCustomerFactory->create(),
                'progressBar' => $progressBar
            ]
        );

        $output->writeln('');
        $output->writeln('<info>Created ' . $this->exportMessages . ' export message(s)</info>');

        return Cli::RETURN_SUCCESS;
    }

    /**
     * @param array $args
     */
    public function callbackExportCustomer(array $args): void
    {
        /** @var Customer $magentoCustomer */
        $magentoCustomer = clone $args['magentoCustomer'];
        $magentoCustomer->setData($args['row']);

        /** @var ProgressBar $progressBar */
        $progressBar = $args['progressBar'];
        $progressBar->advance();

        $this->exportCustomerService->export($magentoCustomer);
        $this->exportMessages++;
    }
}
