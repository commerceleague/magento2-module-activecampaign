<?php
declare(strict_types=1);
/**
 */

namespace CommerceLeague\ActiveCampaign\Console\Command;

use CommerceLeague\ActiveCampaign\Helper\Config as ConfigHelper;
use CommerceLeague\ActiveCampaign\MessageQueue\Topics;
use CommerceLeague\ActiveCampaign\Model\ResourceModel\Quote\CollectionFactory as QuoteCollectionFactory;
use CommerceLeague\ActiveCampaign\vendor\Model\ResourceModel\Quote\Collection as QuoteCollection;
use Magento\Framework\Console\Cli;
use Magento\Framework\MessageQueue\PublisherInterface;
use Symfony\Component\Console\Exception\RuntimeException;
use Symfony\Component\Console\Helper\ProgressBarFactory;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class ExportAbandonedCartCommand
 */
class ExportAbandonedCartCommand extends AbstractExportCommand
{
    private const NAME = 'activecampaign:export:abandoned-cart';
    private const QUOTE_ID = 'quote-id';
    private const OPTION_OMITTED = 'omitted';
    private const OPTION_ALL = 'all';

    /**
     * @param ConfigHelper $configHelper
     * @param QuoteCollectionFactory $quoteCollectionFactory
     * @param ProgressBarFactory $progressBarFactory
     * @param PublisherInterface $publisher
     */
    public function __construct(
        ConfigHelper $configHelper,
        private readonly QuoteCollectionFactory $quoteCollectionFactory,
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
            ->setDescription('Export abandoned carts')
            ->addOption(
                self::QUOTE_ID,
                null,
                InputOption::VALUE_REQUIRED,
                'The quote id'
            )
            ->addOption(
                self::OPTION_OMITTED,
                null,
                InputOption::VALUE_NONE,
                'Only export omitted abandoned carts'
            )
            ->addOption(
                self::OPTION_ALL,
                null,
                InputOption::VALUE_NONE,
                'Export all abandoned carts'
            );
    }

    /**
     * @inheritDoc
     */
    protected function interact(InputInterface $input, OutputInterface $output)
    {
        if (!$this->configHelper->isEnabled() || !$this->configHelper->isAbandonedCartExportEnabled()) {
            throw new RuntimeException('Export disabled by system configuration');
        }

        $quoteId = $input->getOption(self::QUOTE_ID);
        $omitted = $input->getOption(self::OPTION_OMITTED);
        $all = $input->getOption(self::OPTION_ALL);

        if ($quoteId === null && $omitted === false && $all === false) {
            throw new RuntimeException('Please provide at least one option');
        }

        if ($quoteId !== null && ($omitted === true || $all === true)) {
            throw new RuntimeException('You cannot use --quote-id together with another option');
        }

        if ($omitted === true && $all === true) {
            throw new RuntimeException('You cannot use --omitted and --all together');
        }
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int|void|null
     * @throws \Exception
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $quoteIds = $this->getQuoteIds($input);
        $quoteIdsCount = count($quoteIds);

        if ($quoteIdsCount === 0) {
            $output->writeln('<error>No abandoned cart(s) found matching your criteria</error>');
            return Cli::RETURN_FAILURE;
        }

        $progressBar = $this->createProgressBar(
            $output,
            $quoteIdsCount,
            'Abandoned Cart(s)'
        );

        foreach ($quoteIds as $quoteId) {
            $this->publisher->publish(
                Topics::QUOTE_ABANDONED_CART_EXPORT,
                json_encode(['quote_id' => $quoteId], JSON_THROW_ON_ERROR)
            );

            $progressBar->advance();
        }

        $output->writeln('');
        $output->writeln(sprintf(
                '<info>%s abandoned cart(s) have been scheduled for export.</info>',
                ($quoteIdsCount)
        ));

        return Cli::RETURN_SUCCESS;
    }

    /**
     * @return array
     * @throws \Exception
     */
    private function getQuoteIds(InputInterface $input): array
    {
        /** @var QuoteCollection $quoteCollection */
        $quoteCollection = $this->quoteCollectionFactory->create();
        $quoteCollection->addAbandonedFilter();

        if (($quoteId = $input->getOption(self::QUOTE_ID))) {
            $quoteCollection->addIdFilter((int)$quoteId);
        }

        if ($input->getOption(self::OPTION_OMITTED)) {
            $quoteCollection->addOmittedFilter();
        }

        return $quoteCollection->getAllIds();
    }
}
