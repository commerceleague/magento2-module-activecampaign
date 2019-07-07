<?php
declare(strict_types=1);
/**
 */

namespace CommerceLeague\ActiveCampaign\Console\Command;

use CommerceLeague\ActiveCampaign\MessageQueue\Topics;
use Magento\Customer\Model\ResourceModel\Customer\Collection as MagentoCustomerCollection;
use Magento\Customer\Model\ResourceModel\Customer\CollectionFactory as MagentoCustomerCollectionFactory;
use Magento\Framework\Console\Cli;
use Magento\Framework\MessageQueue\PublisherInterface;
use Magento\Newsletter\Model\ResourceModel\Subscriber\CollectionFactory as SubscriberCollectionFactory;
use Magento\Newsletter\Model\ResourceModel\Subscriber\Collection as SubscriberCollection;
use Magento\Newsletter\Model\Subscriber;
use Symfony\Component\Console\Helper\ProgressBarFactory;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class ExportContactCommand
 */
class ExportContactCommand extends AbstractExportCommand
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
     * @param MagentoCustomerCollectionFactory $magentoCustomerCollectionFactory
     * @param SubscriberCollectionFactory $subscriberCollectionFactory
     * @param PublisherInterface $publisher
     * @param ProgressBarFactory $progressBarFactory
     */
    public function __construct(
        MagentoCustomerCollectionFactory $magentoCustomerCollectionFactory,
        SubscriberCollectionFactory $subscriberCollectionFactory,
        PublisherInterface $publisher,
        ProgressBarFactory $progressBarFactory
    ) {
        $this->magentoCustomerCollectionFactory = $magentoCustomerCollectionFactory;
        $this->subscriberCollectionFactory = $subscriberCollectionFactory;
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
                Topics::CUSTOMER_CONTACT_EXPORT,
                json_encode(['magento_customer_id' => $magentoCustomerId])
            );

            $progressBar->advance();
        }

        $output->writeln('');

        $subscriberEmails = $this->getSubscriberEmails();

        $progressBar = $this->createProgressBar(
            $output,
            count($subscriberEmails),
            'Export Newsletter Subscriber(s)'
        );

        foreach ($subscriberEmails as $subscriberEmail) {
            $this->publisher->publish(
                Topics::NEWSLETTER_CONTACT_EXPORT,
                json_encode(['email' => $subscriberEmail])
            );

            $progressBar->advance();
        }

        $output->writeln('');
        $output->writeln(sprintf(
            '<info>Exported %s contact(s)</info>',
            (count($magentoCustomerIds) + count($subscriberEmails)))
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

    /**
     * @return array
     */
    private function getSubscriberEmails(): array
    {
        /** @var SubscriberCollection $subscriberCollection */
        $subscriberCollection = $this->subscriberCollectionFactory->create();
        $subscriberCollection->addFieldToFilter('customer_id', 0);

        $subscriberEmails = [];

        /** @var Subscriber $subscriber */
        foreach ($subscriberCollection as $subscriber) {
            $subscriberEmails[] = $subscriber->getEmail();
        }

        return $subscriberEmails;
    }
}
