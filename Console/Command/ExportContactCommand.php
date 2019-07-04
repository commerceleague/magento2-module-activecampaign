<?php
declare(strict_types=1);
/**
 */

namespace CommerceLeague\ActiveCampaign\Console\Command;

use CommerceLeague\ActiveCampaign\Service\ExportContactService;
use Magento\Customer\Model\Customer;
use Magento\Customer\Model\CustomerFactory as MagentoCustomerFactory;
use Magento\Customer\Model\ResourceModel\Customer\Collection as MagentoCustomerCollection;
use Magento\Customer\Model\ResourceModel\Customer\CollectionFactory as MagentoCustomerCollectionFactory;
use Magento\Framework\Console\Cli;
use Magento\Framework\Model\ResourceModel\Iterator as ResourceIterator;
use Magento\Newsletter\Model\ResourceModel\Subscriber\CollectionFactory as SubscriberCollectionFactory;
use Magento\Newsletter\Model\ResourceModel\Subscriber\Collection as SubscriberCollection;
use Magento\Newsletter\Model\Subscriber;
use Magento\Newsletter\Model\SubscriberFactory;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Helper\ProgressBarFactory;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class ExportContactCommand
 */
class ExportContactCommand extends Command
{
    private const NAME = 'activecampaign:export:contact';

    /**
     * @var MagentoCustomerCollectionFactory
     */
    private $magentoCustomerCollectionFactory;

    /**
     * @var SubscriberCollectionFactory
     */
    private $subscriberCollectionFactory;

    /**
     * @var MagentoCustomerFactory
     */
    private $magentoCustomerFactory;

    /**
     * @var SubscriberFactory
     */
    private $subscriberFactory;

    /**
     * @var ProgressBarFactory
     */
    private $progressBarFactory;

    /**
     * @var ResourceIterator
     */
    private $resourceIterator;

    /**
     * @var ExportContactService
     */
    private $exportContactService;

    /**
     * @var array
     */
    private $processedEmails = [];

    /**
     * @var int
     */
    private $exportMessages = 0;

    /**
     * @param MagentoCustomerCollectionFactory $magentoCustomerCollectionFactory
     * @param SubscriberCollectionFactory $subscriberCollectionFactory
     * @param MagentoCustomerFactory $magentoCustomerFactory
     * @param SubscriberFactory $subscriberFactory
     * @param ProgressBarFactory $progressBarFactory
     * @param ResourceIterator $resourceIterator
     * @param ExportContactService $exportContactService
     */
    public function __construct(
        MagentoCustomerCollectionFactory $magentoCustomerCollectionFactory,
        SubscriberCollectionFactory $subscriberCollectionFactory,
        MagentoCustomerFactory $magentoCustomerFactory,
        SubscriberFactory $subscriberFactory,
        ProgressBarFactory $progressBarFactory,
        ResourceIterator $resourceIterator,
        ExportContactService $exportContactService
    ) {
        $this->magentoCustomerCollectionFactory = $magentoCustomerCollectionFactory;
        $this->subscriberCollectionFactory = $subscriberCollectionFactory;
        $this->magentoCustomerFactory = $magentoCustomerFactory;
        $this->subscriberFactory = $subscriberFactory;
        $this->progressBarFactory = $progressBarFactory;
        $this->resourceIterator = $resourceIterator;
        $this->exportContactService = $exportContactService;
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

        /** @var SubscriberCollection $subscriberCollection */
        $subscriberCollection = $this->subscriberCollectionFactory->create();

        if ($magentoCustomerCollection->getSize() === 0 && $subscriberCollection->getSize() === 0) {
            $output->writeln('<error>No valid magento customers or subscribers found</error>');
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

        $this->resourceIterator->walk(
            $subscriberCollection->getSelect(),
            [[$this, 'callbackExportSubscriber']],
            [
                'subscriber' => $this->subscriberFactory->create(),
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

        $this->exportContactService->exportWithMagentoCustomer($magentoCustomer);
        $this->processedEmails[$magentoCustomer->getData('email')] = null;
        $this->exportMessages++;
    }

    /**
     * @param array $args
     */
    public function callbackExportSubscriber(array $args): void
    {
        /** @var Subscriber $subscriber */
        $subscriber = clone $args['subscriber'];
        $subscriber->setData($args['row']);

        if (array_key_exists($subscriber->getData('email'), $this->processedEmails)) {
            return;
        }

        /** @var ProgressBar $progressBar */
        $progressBar = $args['progressBar'];
        $progressBar->advance();

        $this->exportContactService->exportWithSubscriber($subscriber);
        $this->exportMessages++;
    }
}
