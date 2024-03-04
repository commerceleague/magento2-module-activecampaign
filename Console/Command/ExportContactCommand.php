<?php
declare(strict_types=1);
/**
 */

namespace CommerceLeague\ActiveCampaign\Console\Command;

use CommerceLeague\ActiveCampaign\Helper\Config as ConfigHelper;
use CommerceLeague\ActiveCampaign\MessageQueue\Topics;
use CommerceLeague\ActiveCampaign\Model\ResourceModel\Customer\CollectionFactory as CustomerCollectionFactory;
use CommerceLeague\ActiveCampaign\Model\ResourceModel\Subscriber\CollectionFactory as SubscriberCollectionFactory;
use CommerceLeague\ActiveCampaign\vendor\Model\ResourceModel\Customer\Collection as CustomerCollection;
use CommerceLeague\ActiveCampaign\vendor\Model\ResourceModel\Subscriber\Collection as SubscriberCollection;
use Magento\Framework\Console\Cli;
use Magento\Framework\MessageQueue\PublisherInterface;
use Symfony\Component\Console\Exception\RuntimeException;
use Symfony\Component\Console\Helper\ProgressBarFactory;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class ExportContactCommand
 */
class ExportContactCommand extends AbstractExportCommand
{
    private const NAME = 'activecampaign:export:contact';
    private const OPTION_EMAIL = 'email';
    private const OPTION_OMITTED = 'omitted';
    private const OPTION_ALL = 'all';

    /**
     * @param ProgressBarFactory $progressBarFactory
     */
    public function __construct(
        ConfigHelper $configHelper,
        private readonly CustomerCollectionFactory $customerCollectionFactory,
        private readonly SubscriberCollectionFactory $subscriberCollectionFactory,
        ProgressBarFactory $progressBarFactory,
        PublisherInterface $publisher
    ) {
        parent::__construct($configHelper, $progressBarFactory, $publisher);
    }

    /**
     * @inheritDoc
     */
    protected function configure()
    {
        $this->setName(self::NAME)
            ->setDescription('Export contacts')
            ->addOption(
                self::OPTION_EMAIL,
                null,
                InputOption::VALUE_REQUIRED,
                'The contact email'
            )
            ->addOption(
                self::OPTION_OMITTED,
                null,
                InputOption::VALUE_NONE,
                'Only export omitted contacts'
            )
            ->addOption(
                self::OPTION_ALL,
                null,
                InputOption::VALUE_NONE,
                'Export all contacts'
            );
    }

    /**
     * @inheritDoc
     */
    protected function interact(InputInterface $input, OutputInterface $output)
    {
        if (!$this->configHelper->isEnabled() || !$this->configHelper->isContactExportEnabled()) {
            throw new RuntimeException('Export disabled by system configuration');
        }

        $email = $input->getOption(self::OPTION_EMAIL);
        $omitted = $input->getOption(self::OPTION_OMITTED);
        $all = $input->getOption(self::OPTION_ALL);

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
        $customerIds = $this->getCustomerIds($input);
        $customerIdsCount = count($customerIds);
        $subscriberEmails = $this->getSubscriberEmails($input);
        $subscriberEmailsCount = count($subscriberEmails);

        if (($customerIdsCount + $subscriberEmailsCount) === 0) {
            $output->writeln('<error>No contact(s) found matching your criteria</error>');
            return Cli::RETURN_FAILURE;
        }

        if ($customerIdsCount > 0) {
            $progressBar = $this->createProgressBar(
                $output,
                $customerIdsCount,
                'Customer(s)'
            );

            foreach ($customerIds as $customerId) {
                $this->publisher->publish(
                    Topics::CUSTOMER_CONTACT_EXPORT,
                    json_encode(['magento_customer_id' => $customerId], JSON_THROW_ON_ERROR)
                );

                $progressBar->advance();
            }

            $output->writeln('');
        }

        if ($subscriberEmailsCount > 0) {
            $progressBar = $this->createProgressBar(
                $output,
                $subscriberEmailsCount,
                'Subscriber(s)'
            );

            foreach ($subscriberEmails as $subscriberEmail) {
                $this->publisher->publish(
                    Topics::NEWSLETTER_CONTACT_EXPORT,
                    json_encode(['email' => $subscriberEmail], JSON_THROW_ON_ERROR)
                );

                $progressBar->advance();
            }

            $output->writeln('');
        }

        $output->writeln(sprintf(
                '<info>%s contact(s) have been scheduled for export.</info>',
            ($customerIdsCount + $subscriberEmailsCount)
        ));

        return Cli::RETURN_SUCCESS;
    }

    private function getCustomerIds(InputInterface $input): array
    {
        /** @var CustomerCollection $customerCollection */
        $customerCollection = $this->customerCollectionFactory->create();

        if (($email = $input->getOption(self::OPTION_EMAIL)) !== null) {
            $customerCollection->addEmailFilter($email);
        }

        if ($input->getOption(self::OPTION_OMITTED)) {
            $customerCollection->addContactOmittedFilter();
        }

        return $customerCollection->getAllIds();
    }

    private function getSubscriberEmails(InputInterface $input): array
    {
        /** @var SubscriberCollection $subscriberCollection */
        $subscriberCollection = $this->subscriberCollectionFactory->create();
        $subscriberCollection->excludeCustomers();

        if (($email = $input->getOption(self::OPTION_EMAIL)) !== null) {
            $subscriberCollection->addEmailFilter($email);
        }

        if ($input->getOption(self::OPTION_OMITTED)) {
            $subscriberCollection->addContactOmittedFilter();
        }

        return $subscriberCollection->getAllEmails();
    }
}
