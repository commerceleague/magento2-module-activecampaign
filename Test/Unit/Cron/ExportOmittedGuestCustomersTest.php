<?php
declare(strict_types=1);
/**
 */

namespace CommerceLeague\ActiveCampaign\Test\Unit\Cron;

use CommerceLeague\ActiveCampaign\Cron\PublishOmittedGuestCustomers;
use CommerceLeague\ActiveCampaign\Helper\Config as ConfigHelper;
use CommerceLeague\ActiveCampaign\Model\ResourceModel\ActiveCampaign\GuestCustomer\CollectionFactory as CustomerCollectionFactory;
use CommerceLeague\ActiveCampaign\Model\ResourceModel\ActiveCampaign\GuestCustomer\Collection as CustomerCollection;
use CommerceLeague\ActiveCampaign\Test\Unit\AbstractTestCase;
use Magento\Framework\MessageQueue\PublisherInterface;
use PHPUnit\Framework\MockObject\MockObject;

class ExportOmittedGuestCustomersTest extends AbstractTestCase
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
     * @var PublishOmittedGuestCustomers
     */
    protected $exportOmittedGuestCustomers;

    protected function setUp(): void
    {
        $this->configHelper = $this->createMock(ConfigHelper::class);

        $this->customerCollectionFactory = $this->getMockBuilder(CustomerCollectionFactory::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['create'])
            ->getMock();

        $this->customerCollection = $this->createMock(CustomerCollection::class);

        $this->customerCollectionFactory->expects($this->any())
            ->method('create')
            ->willReturn($this->customerCollection);

        $this->publisher = $this->createMock(PublisherInterface::class);

        $this->exportOmittedGuestCustomers = new PublishOmittedGuestCustomers(
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
            ->method('addOmittedFilter');

        $this->exportOmittedGuestCustomers->run();
    }

    public function testRunCustomerExportDisabled()
    {
        $this->configHelper->expects($this->once())
            ->method('isEnabled')
            ->willReturn(true);

        $this->configHelper->expects($this->once())
            ->method('isCustomerExportEnabled')
            ->willReturn(false);

        $this->customerCollection->expects($this->never())
            ->method('addOmittedFilter');

        $this->exportOmittedGuestCustomers->run();
    }

    public function testRun()
    {
        $this->configHelper->expects($this->once())
            ->method('isEnabled')
            ->willReturn(true);

        $this->configHelper->expects($this->once())
            ->method('isCustomerExportEnabled')
            ->willReturn(true);

        $this->configHelper->expects($this->once())
            ->method('isRetryAllOmittedEnabled')
            ->willReturn(false);

        $this->customerCollection->expects($this->once())
            ->method('addOmittedFilter')
            ->willReturnSelf();

        $this->customerCollection->expects($this->once())
            ->method('addNotDeadLetteredFilter')
            ->willReturnSelf();

        $this->customerCollection->expects($this->once())
            ->method('addExportFilterOrderStatus')
            ->willReturnSelf();

        $this->customerCollection->expects($this->once())
            ->method('addExportFilterStartDate')
            ->willReturnSelf();

        $this->customerCollection->expects($this->once())
            ->method('getItems')
            ->willReturn([]);

        $this->publisher->expects($this->never())
            ->method('publish');

        $this->exportOmittedGuestCustomers->run();
    }

    public function testRunWithRetryAllOmittedSkipsWindowFilters()
    {
        $this->configHelper->expects($this->once())
            ->method('isEnabled')
            ->willReturn(true);

        $this->configHelper->expects($this->once())
            ->method('isCustomerExportEnabled')
            ->willReturn(true);

        $this->configHelper->expects($this->once())
            ->method('isRetryAllOmittedEnabled')
            ->willReturn(true);

        $this->customerCollection->expects($this->once())
            ->method('addOmittedFilter')
            ->willReturnSelf();

        $this->customerCollection->expects($this->once())
            ->method('addNotDeadLetteredFilter')
            ->willReturnSelf();

        $this->customerCollection->expects($this->never())
            ->method('addExportFilterOrderStatus');

        $this->customerCollection->expects($this->never())
            ->method('addExportFilterStartDate');

        $this->customerCollection->expects($this->once())
            ->method('getItems')
            ->willReturn([]);

        $this->exportOmittedGuestCustomers->run();
    }
}
