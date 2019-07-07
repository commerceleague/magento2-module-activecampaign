<?php
declare(strict_types=1);
/**
 */

namespace CommerceLeague\ActiveCampaign\Console\Command;

use CommerceLeague\ActiveCampaign\Helper\AbandonedCart as AbandonedCartHelper;
use CommerceLeague\ActiveCampaign\MessageQueue\Topics;
use Magento\Framework\Console\Cli;
use Magento\Framework\MessageQueue\PublisherInterface;
use Symfony\Component\Console\Helper\ProgressBarFactory;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class ExportAbandonedCommand
 */
class ExportAbandonedCommand extends AbstractExportCommand
{
    private const NAME = 'activecampaign:export:abandoned-cart';

    /**
     * @var AbandonedCartHelper
     */
    private $abandonedCartHelper;

    /**
     * @param AbandonedCartHelper $abandonedCartHelper
     * @param ProgressBarFactory $progressBarFactory
     * @param PublisherInterface $publisher
     */
    public function __construct(
        AbandonedCartHelper $abandonedCartHelper,
        ProgressBarFactory $progressBarFactory,
        PublisherInterface $publisher
    ) {
        $this->abandonedCartHelper = $abandonedCartHelper;
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
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int|void|null
     * @throws \Exception
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $quoteIds = $this->abandonedCartHelper->getExportCollection()->getAllIds();

        $progressBar = $this->createProgressBar(
            $output,
            count($quoteIds),
            'Export Abandoned Cart(s)'
        );

        foreach ($quoteIds as $quoteId) {
            $this->publisher->publish(
                Topics::QUOTE_ABANDONED_CART_EXPORT,
                json_encode(['quote_id' => $quoteId])
            );

            $progressBar->advance();
        }

        $output->writeln('');
        $output->writeln(sprintf(
                '<info>Exported %s abandoned cart(s)</info>',
                (count($quoteIds)))
        );

        return Cli::RETURN_SUCCESS;
    }
}
