<?php
declare(strict_types=1);
/**
 */

namespace CommerceLeague\ActiveCampaign\Test\Observer\Customer;

use CommerceLeague\ActiveCampaign\Helper\Config as ConfigHelper;
use CommerceLeague\ActiveCampaign\Observer\Customer\ExportContactObserver;
use CommerceLeague\ActiveCampaign\Service\ExportContactService;
use Magento\Customer\Model\Customer;
use Magento\Framework\Event;
use Magento\Framework\Event\Observer;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class ExportContactObserverTest extends TestCase
{
    /**
     * @var MockObject|ConfigHelper
     */
    protected $configHelper;

    /**
     * @var MockObject|ExportContactService
     */
    protected $exportContactService;

    /**
     * @var MockObject|Observer
     */
    protected $observer;

    /**
     * @var MockObject|Event
     */
    protected $event;

    /**
     * @var MockObject|Customer
     */
    protected $customer;

    /**
     * @var ExportContactService
     */
    protected $exportContactObserver;

    protected function setUp()
    {
        $this->configHelper = $this->createMock(ConfigHelper::class);
        $this->exportContactService = $this->createMock(ExportContactService::class);
        $this->observer = $this->createMock(Observer::class);
        $this->event = $this->createMock(Event::class);
        $this->customer = $this->createMock(Customer::class);

        $this->exportContactObserver = new ExportContactObserver(
            $this->configHelper,
            $this->exportContactService
        );
    }

    public function testExecuteApiDisabled()
    {
        $this->configHelper->expects($this->once())
            ->method('isApiEnabled')
            ->willReturn(false);

        $this->observer->expects($this->never())
            ->method('getEvent');

        $this->exportContactObserver->execute($this->observer);
    }

    public function testExecute()
    {
        $this->configHelper->expects($this->once())
            ->method('isApiEnabled')
            ->willReturn(true);

        $this->observer->expects($this->once())
            ->method('getEvent')
            ->willReturn($this->event);

        $this->event->expects($this->once())
            ->method('getData')
            ->with('customer')
            ->willReturn($this->customer);

        $this->exportContactService->expects($this->once())
            ->method('exportWithMagentoCustomer')
            ->with($this->customer);

        $this->exportContactObserver->execute($this->observer);
    }
}
