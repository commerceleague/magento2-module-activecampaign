<?php
declare(strict_types=1);
/**
 */

namespace CommerceLeague\ActiveCampaign\Console\Command;

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
     * @var PublisherInterface
     */
    protected $publisher;

    /**
     * @param ProgressBarFactory $progressBarFactory
     * @param PublisherInterface $publisher
     */
    public function __construct(
        ProgressBarFactory $progressBarFactory,
        PublisherInterface $publisher
    ) {
        $this->progressBarFactory = $progressBarFactory;
        $this->publisher = $publisher;
        parent::__construct();
    }

    /**
     * @param OutputInterface $output
     * @param string $message
     * @param int $max
     * @return ProgressBar
     */
    protected function createProgressBar(OutputInterface $output, int $max, string $message): ProgressBar
    {
        /** @var ProgressBar $progressBar */
        $progressBar = $this->progressBarFactory->create(['output' => $output, 'max' => $max]);
        $progressBar->setFormat('<comment>%message%</comment> %current%/%max% [%bar%] %percent:3s%% %elapsed%');
        $progressBar->setMessage($message);

        return $progressBar;
    }
}
