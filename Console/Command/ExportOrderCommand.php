<?php
declare(strict_types=1);
/**
 */

namespace CommerceLeague\ActiveCampaign\Console\Command;

use CommerceLeague\ActiveCampaign\MessageQueue\Topics;
use Magento\Framework\Console\Cli;
use Magento\Framework\MessageQueue\PublisherInterface;
use Symfony\Component\Console\Helper\ProgressBarFactory;
use Magento\Sales\Model\ResourceModel\Order\CollectionFactory as MagentoOrderCollectionFactory;
use Magento\Sales\Model\ResourceModel\Order\Collection as MagentoOrderCollection;
use Magento\Sales\Model\Order as MagentoOrder;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;


/**
 * Class ExportOrderCommand
 */
class ExportOrderCommand extends AbstractExportCommand
{
    private const NAME = 'activecampaign:export:order';

    /**
     * @var MagentoOrderCollectionFactory
     */
    private $magentoOrderCollectionFactory;

    /**
     * @param MagentoOrderCollectionFactory $magentoOrderCollectionFactory
     * @param PublisherInterface $publisher
     * @param ProgressBarFactory $progressBarFactory
     */
    public function __construct(
        MagentoOrderCollectionFactory $magentoOrderCollectionFactory,
        PublisherInterface $publisher,
        ProgressBarFactory $progressBarFactory
    ) {
        $this->magentoOrderCollectionFactory = $magentoOrderCollectionFactory;
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
        $magentoOrderIds = $this->getMagentoOrderIds();

        $progressBar = $this->createProgressBar(
            $output,
            count($magentoOrderIds),
            'Export Magento Order(s)'
        );

        foreach ($magentoOrderIds as $magentoOrderId) {
            $this->publisher->publish(
                Topics::SALES_ORDER_EXPORT,
                json_encode(['magento_order_id' => $magentoOrderId])
            );

            $progressBar->advance();
        }

        $output->writeln('');
        $output->writeln(sprintf(
                '<info>Exported %s order(s)</info>',
                (count($magentoOrderIds)))
        );

        return Cli::RETURN_SUCCESS;
    }

    /**
     * @return array
     */
    private function getMagentoOrderIds(): array
    {
        /** @var MagentoOrderCollection $magentoOrderCollection */
        $magentoOrderCollection = $this->magentoOrderCollectionFactory->create();
        $magentoOrderCollection->addFieldToFilter(MagentoOrder::STATUS, MagentoOrder::STATE_COMPLETE);
        $magentoOrderCollection->addFieldToFilter(MagentoOrder::CUSTOMER_IS_GUEST, false);

        return $magentoOrderCollection->getAllIds();
    }
}
