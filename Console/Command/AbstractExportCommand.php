<?php
declare(strict_types=1);
/**
 */

namespace CommerceLeague\ActiveCampaign\Console\Command;

use CommerceLeague\ActiveCampaign\Helper\Config as ConfigHelper;
use Magento\Framework\MessageQueue\PublisherInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Helper\ProgressBarFactory;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class AbstractExportCommand
 */
abstract class AbstractExportCommand extends Command
{
    /**
     * @var ProgressBarFactory
     */
    private $progressBarFactory;

    /**
     * @param ProgressBarFactory $progressBarFactory
     */
    public function __construct(
        protected ConfigHelper $configHelper,
        ProgressBarFactory $progressBarFactory,
        protected \Magento\Framework\MessageQueue\PublisherInterface $publisher
    ) {
        $this->progressBarFactory = $progressBarFactory;
        parent::__construct();
    }

    protected function createProgressBar(OutputInterface $output, int $max, string $message): ProgressBar
    {
        /** @var ProgressBar $progressBar */
        $progressBar = $this->progressBarFactory->create(['output' => $output, 'max' => $max]);
        $progressBar->setFormat('<comment>%message%</comment> %current%/%max% [%bar%] %percent:3s%% %elapsed%');
        $progressBar->setMessage($message);

        return $progressBar;
    }
}
