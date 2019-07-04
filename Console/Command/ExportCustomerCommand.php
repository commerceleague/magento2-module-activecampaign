<?php
declare(strict_types=1);
/**
 */

namespace CommerceLeague\ActiveCampaign\Console\Command;

use CommerceLeague\ActiveCampaign\Service\ExportCustomerService;
use Magento\Customer\Model\Customer;
use Magento\Customer\Model\CustomerFactory as MagentoCustomerFactory;
use Magento\Customer\Model\ResourceModel\Customer\CollectionFactory as MagentoCustomerCollectionFactory;
use Magento\Customer\Model\ResourceModel\Customer\Collection as MagentoCustomerCollection;
use Magento\Framework\Console\Cli;
use Magento\Framework\Model\ResourceModel\Iterator as ResourceIterator;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProgressBarFactory;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class ExportContactCommand
 */
class ExportCustomerCommand extends Command
{
    private const NAME = 'activecampaign:export:customer';

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
     * @param ExportCustomerService $exportContactService
     */
    public function __construct(
        MagentoCustomerCollectionFactory $magentoCustomerCollectionFactory,
        MagentoCustomerFactory $magentoCustomerFactory,
        ProgressBarFactory $progressBarFactory,
        ResourceIterator $resourceIterator,
        ExportCustomerService $exportContactService
    ) {
        $this->magentoCustomerCollectionFactory = $magentoCustomerCollectionFactory;
        $this->magentoCustomerFactory = $magentoCustomerFactory;
        $this->progressBarFactory = $progressBarFactory;
        $this->resourceIterator = $resourceIterator;
        $this->exportCustomerService = $exportContactService;
        parent::__construct();
    }

    /**
     * @inheritDoc
     */
    protected function configure()
    {
        $this->setName(self::NAME);
    }

    /**
     * @inheritDoc
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        /** @var MagentoCustomerCollection $magentoCustomerCollection */
        $magentoCustomerCollection = $this->magentoCustomerCollectionFactory->create();

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
            [[$this, 'callbackExportMagentoCustomer']],
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
    public function callbackExportMagentoCustomer(array $args): void
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
