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
     * @var PublishOmittedCustomers
     */
    protected $exportOmittedCustomers;

    protected function setUp()
    {
        $this->configHelper = $this->createMock(ConfigHelper::class);

        $this->customerCollectionFactory = $this->getMockBuilder(CustomerCollectionFactory::class)
            ->disableOriginalConstructor()
            ->setMethods(['create'])
            ->getMock();

        $this->customerCollection = $this->createMock(CustomerCollection::class);

        $this->customerCollectionFactory->expects($this->any())
            ->method('create')
            ->willReturn($this->customerCollection);

        $this->publisher = $this->createMock(PublisherInterface::class);

        $this->exportOmittedCustomers = new PublishOmittedCustomers(
            $this->configHelper,
            $this->customerCollectionFactory,
            $this->publisher
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

        $this->customerCollection->expects($this->once())
            ->method('addCustomerOmittedFilter')
            ->willReturnSelf();

        $this->customerCollection->expects($this->once())
            ->method('getAllIds')
            ->willReturn($customerIds);

        $this->publisher->expects($this->exactly(2))
            ->method('publish');

        $this->publisher->expects($this->at(0))
            ->method('publish')
            ->with(
                Topics::CUSTOMER_CUSTOMER_EXPORT,
                json_encode(['magento_customer_id' => $customerIds[0]])
            );

        $this->publisher->expects($this->at(1))
            ->method('publish')
            ->with(
                Topics::CUSTOMER_CUSTOMER_EXPORT,
                json_encode(['magento_customer_id' => $customerIds[1]])
            );

        $this->exportOmittedCustomers->run();
    }
}
