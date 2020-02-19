<?php
declare(strict_types=1);
/**
 */

namespace CommerceLeague\ActiveCampaign\Console\Command;

use CommerceLeague\ActiveCampaign\Api\Data\GuestCustomerInterface;
use CommerceLeague\ActiveCampaign\Helper\Config as ConfigHelper;
use CommerceLeague\ActiveCampaign\MessageQueue\Topics;
use CommerceLeague\ActiveCampaign\Model\ResourceModel\GuestCustomer\CollectionFactory as CustomerCollectionFactory;
use CommerceLeague\ActiveCampaign\Model\ResourceModel\GuestCustomer\Collection as CustomerCollection;
use Magento\Framework\Console\Cli;
use Magento\Framework\MessageQueue\PublisherInterface;
use Magento\Sales\Api\Data\OrderInterface;
use Symfony\Component\Console\Exception\RuntimeException;
use Symfony\Component\Console\Helper\ProgressBarFactory;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class ExportGuestCustomerCommand extends AbstractExportCommand
{

    private const NAME           = 'activecampaign:export:guest-customer';
    private const OPTION_EMAIL   = 'email';
    private const OPTION_OMITTED = 'omitted';
    private const OPTION_ALL     = 'all';

    /**
     * @var CustomerCollectionFactory
     */
    private $customerCollectionFactory;

    /**
     * @param ConfigHelper              $configHelper
     * @param CustomerCollectionFactory $customerCollectionFactory
     * @param ProgressBarFactory        $progressBarFactory
     * @param PublisherInterface        $publisher
     */
    public function __construct(
        ConfigHelper $configHelper,
        CustomerCollectionFactory $customerCollectionFactory,
        ProgressBarFactory $progressBarFactory,
        PublisherInterface $publisher
    ) {
        $this->customerCollectionFactory = $customerCollectionFactory;
        parent::__construct($configHelper, $progressBarFactory, $publisher);
    }

    /**
     * @inheritDoc
     */
    protected function configure()
    {
        $this->setName(self::NAME)
            ->setDescription('Export Guest customers')
            ->addOption(
                self::OPTION_EMAIL,
                null,
                InputOption::VALUE_REQUIRED,
                'The customer email'
            )
            ->addOption(
                self::OPTION_OMITTED,
                null,
                InputOption::VALUE_NONE,
                'Only export omitted customers'
            )
            ->addOption(
                self::OPTION_ALL,
                null,
                InputOption::VALUE_NONE,
                'Export all customers'
            );
    }

    /**
     * @inheritDoc
     */
    protected function interact(InputInterface $input, OutputInterface $output)
    {
        if (!$this->configHelper->isEnabled() || !$this->configHelper->isCustomerExportEnabled()) {
            throw new RuntimeException('Export disabled by system configuration');
        }

        $email   = $input->getOption(self::OPTION_EMAIL);
        $omitted = $input->getOption(self::OPTION_OMITTED);
        $all     = $input->getOption(self::OPTION_ALL);

        if ($email === null && $omitted === false && $all === false) {
            throw new RuntimeException('Please provide at least one option');
        }

        if ($email !== null && ($omitted === true || $all === true)) {
            throw new RuntimeException('You cannot use --email together with another option');
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
        $customers        = $this->getGuestCustomers($input);
        $customerIdsCount = count($customers);

        if ($customerIdsCount === 0) {
            $output->writeln('<error>No customer(s) found matching your criteria</error>');
            return Cli::RETURN_FAILURE;
        }

        $progressBar = $this->createProgressBar(
            $output,
            $customerIdsCount,
            'Guest Customer(s)'
        );

        /** @var OrderInterface $customer */
        foreach ($customers as $customer) {

            $this->publisher->publish(
                Topics::GUEST_CUSTOMER_EXPORT,
                json_encode(
                    [
                        'magento_customer_id' => null,
                        'customer_is_guest'   => true,
                        'customer_data'       => [
                            GuestCustomerInterface::FIRSTNAME => $customer->getCustomerFirstname(),
                            GuestCustomerInterface::LASTNAME  => $customer->getCustomerLastname(),
                            GuestCustomerInterface::EMAIL     => $customer->getCustomerEmail()
                        ]
                    ]
                )
            );

            $progressBar->advance();
        }

        $output->writeln('');
        $output->writeln(
            sprintf(
                '<info>%s guest customers(s) have been scheduled for export.</info>',
                ($customerIdsCount)
            )
        );

        return Cli::RETURN_SUCCESS;
    }

    /**
     * @param InputInterface $input
     *
     * @return array
     */
    private function getGuestCustomers(InputInterface $input): array
    {
        /** @var CustomerCollection $customerCollection */
        $customerCollection = $this->customerCollectionFactory->create();

        $customerCollection->addFieldToFilter(
            'status',
            ['in' => ['complete', 'processing_mgm', 'pending']]
        );

        if (($email = $input->getOption(self::OPTION_EMAIL)) !== null) {
            $customerCollection->addEmailFilter($email);
        }

        if ($input->getOption(self::OPTION_OMITTED)) {
            $customerCollection->addOmittedFilter();
        }

        return $customerCollection->getItems();
    }
}
