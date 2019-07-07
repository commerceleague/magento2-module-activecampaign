<?php
declare(strict_types=1);
/**
 */

namespace CommerceLeague\ActiveCampaign\Console\Command;

use CommerceLeague\ActiveCampaign\MessageQueue\Topics;
use Magento\Customer\Model\ResourceModel\Customer\CollectionFactory as MagentoCustomerCollectionFactory;
use Magento\Customer\Model\ResourceModel\Customer\Collection as MagentoCustomerCollection;
use Magento\Framework\Console\Cli;
use Magento\Framework\MessageQueue\PublisherInterface;
use Symfony\Component\Console\Helper\ProgressBarFactory;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class ExportCustomerCommand
 */
class ExportCustomerCommand extends AbstractExportCommand
{
    private const NAME = 'activecampaign:export:customer';

    /**
     * @var MagentoCustomerCollectionFactory
     */
    private $magentoCustomerCollectionFactory;

    /**
     * @param MagentoCustomerCollectionFactory $magentoCustomerCollectionFactory
     * @param PublisherInterface $publisher
     * @param ProgressBarFactory $progressBarFactory
     */
    public function __construct(
        MagentoCustomerCollectionFactory $magentoCustomerCollectionFactory,
        PublisherInterface $publisher,
        ProgressBarFactory $progressBarFactory
    ) {
        $this->magentoCustomerCollectionFactory = $magentoCustomerCollectionFactory;
        parent::__construct($progressBarFactory, $publisher);
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
        $magentoCustomerIds = $this->getMagentoCustomerIds();

        $progressBar = $this->createProgressBar(
            $output,
            count($magentoCustomerIds),
            'Export Magento Customer(s)'
        );

        foreach ($magentoCustomerIds as $magentoCustomerId) {
            $this->publisher->publish(
                Topics::CUSTOMER_CUSTOMER_EXPORT,
                json_encode(['magento_customer_id' => $magentoCustomerId])
            );

            $progressBar->advance();
        }

        $output->writeln('');
        $output->writeln(sprintf(
                '<info>Exported %s customer(s)</info>',
                (count($magentoCustomerIds)))
        );

        return Cli::RETURN_SUCCESS;
    }

    /**
     * @return array
     */
    private function getMagentoCustomerIds(): array
    {
        /** @var MagentoCustomerCollection $magentoCustomerCollection */
        $magentoCustomerCollection = $this->magentoCustomerCollectionFactory->create();
        return $magentoCustomerCollection->getAllIds();
    }
}
