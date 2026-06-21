<?php
declare(strict_types=1);
/**
 */

namespace CommerceLeague\ActiveCampaign\Test\Unit\Cron;

use CommerceLeague\ActiveCampaign\Cron\PublishOmittedCustomers;
use CommerceLeague\ActiveCampaign\Helper\Config as ConfigHelper;
use CommerceLeague\ActiveCampaign\MessageQueue\Topics;
use CommerceLeague\ActiveCampaign\Model\ResourceModel\Customer\CollectionFactory as CustomerCollectionFactory;
use CommerceLeague\ActiveCampaign\Test\Unit\AbstractTestCase;
use CommerceLeague\ActiveCampaign\Model\ResourceModel\Customer\Collection as CustomerCollection;
use Magento\Framework\MessageQueue\PublisherInterface;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;

class ExportOmittedCustomersTest extends AbstractTestCase
{

    /**
     * @var MockObject|ConfigHelper
     */
    protected $configHelper;

    /**
     * @var MockObject|CustomerCollectionFactory
     */
    protected $customerCollectionFactory;

    /**
     * @var MockObject|CustomerCollection
     */
    protected $customerCollection;

    /**
     * @var MockObject|PublisherInterface
     */
    protected $publisher;

    /**
     * @var MockObject|LoggerInterface
     */
    protected $logger;

    /**
     * @var PublishOmittedCustomers
     */
    protected $exportOmittedCustomers;

    protected function setUp(): void
    {
        $this->configHelper = $this->createMock(ConfigHelper::class);
        $this->logger = $this->createMock(LoggerInterface::class);

        $this->customerCollectionFactory = $this->getMockBuilder(CustomerCollectionFactory::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['create'])
            ->getMock();

        $this->customerCollection = $this->createMock(CustomerCollection::class);

        $this->customerCollectionFactory->expects($this->any())
            ->method('create')
            ->willReturn($this->customerCollection);

        $this->publisher = $this->createMock(PublisherInterface::class);

        $this->exportOmittedCustomers = new PublishOmittedCustomers(
            $this->configHelper,
            $this->customerCollectionFactory,
            $this->publisher,
            $this->logger
        );
    }

    public function testRunDisabled()
    {
        $this->configHelper->expects($this->once())
            ->method('isEnabled')
            ->willReturn(false);

        $this->customerCollection->expects($this->never())
            ->method('addCustomerOmittedFilter');

        $this->exportOmittedCustomers->run();
    }

    public function testRunContactExportDisabled()
    {
        $this->configHelper->expects($this->once())
            ->method('isEnabled')
            ->willReturn(true);

        $this->configHelper->expects($this->once())
            ->method('isCustomerExportEnabled')
            ->willReturn(false);

        $this->customerCollection->expects($this->never())
            ->method('addCustomerOmittedFilter');

        $this->exportOmittedCustomers->run();
    }

    public function testRun()
    {
        $customerIds = [123, 456];

        $this->configHelper->expects($this->once())
            ->method('isEnabled')
            ->willReturn(true);

        $this->configHelper->expects($this->once())
            ->method('isCustomerExportEnabled')
            ->willReturn(true);

        $this->configHelper->expects($this->exactly(2))
            ->method('isRetryAllOmittedEnabled')
            ->willReturn(false);

        $this->logger->expects($this->never())
            ->method('warning');

        $this->customerCollection->expects($this->once())
            ->method('addCustomerOmittedFilter')
            ->with(true)
            ->willReturnSelf();

        $this->customerCollection->expects($this->once())
            ->method('addCustomerNotDeadLetteredFilter')
            ->willReturnSelf();

        $this->customerCollection->expects($this->once())
            ->method('getAllIds')
            ->willReturn($customerIds);

        $expectedPublishArguments = [
            [Topics::CUSTOMER_CUSTOMER_EXPORT, json_encode(['magento_customer_id' => $customerIds[0]])],
            [Topics::CUSTOMER_CUSTOMER_EXPORT, json_encode(['magento_customer_id' => $customerIds[1]])],
        ];
        $actualPublishArguments = [];
        $this->publisher->expects($this->exactly(2))
            ->method('publish')
            ->willReturnCallback(function (...$args) use (&$actualPublishArguments) {
                $actualPublishArguments[] = $args;
            });

        $this->exportOmittedCustomers->run();

        $this->assertSame($expectedPublishArguments, $actualPublishArguments);
    }

    public function testRunWithRetryAllOmittedSkipsCustomerGroupFilter()
    {
        $customerIds = [123, 456];

        $this->configHelper->expects($this->once())
            ->method('isEnabled')
            ->willReturn(true);

        $this->configHelper->expects($this->once())
            ->method('isCustomerExportEnabled')
            ->willReturn(true);

        $this->configHelper->expects($this->exactly(2))
            ->method('isRetryAllOmittedEnabled')
            ->willReturn(true);

        $this->logger->expects($this->once())
            ->method('warning')
            ->with($this->stringContains('retry_all_omitted is ON'));

        $this->customerCollection->expects($this->once())
            ->method('addCustomerOmittedFilter')
            ->with(false)
            ->willReturnSelf();

        $this->customerCollection->expects($this->once())
            ->method('addCustomerNotDeadLetteredFilter')
            ->willReturnSelf();

        $this->customerCollection->expects($this->once())
            ->method('getAllIds')
            ->willReturn($customerIds);

        $this->publisher->expects($this->exactly(2))
            ->method('publish');

        $this->exportOmittedCustomers->run();
    }
}
