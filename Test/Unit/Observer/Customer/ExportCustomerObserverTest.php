<?php
declare(strict_types=1);
/**
 */

namespace CommerceLeague\ActiveCampaign\Test\Observer\Customer;

use CommerceLeague\ActiveCampaign\Helper\Config as ConfigHelper;
use CommerceLeague\ActiveCampaign\Observer\Customer\ExportCustomerObserver;
use CommerceLeague\ActiveCampaign\Service\ExportCustomerService;
use Magento\Customer\Model\Customer;
use Magento\Framework\Event;
use Magento\Framework\Event\Observer;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class ExportCustomerObserverTest extends TestCase
{
    /**
     * @var MockObject|ConfigHelper
     */
    protected $configHelper;

    /**
     * @var MockObject|ExportCustomerService
     */
    protected $exportCustomerService;

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
     * @var ExportCustomerObserver
     */
    protected $exportCustomerObserver;

    protected function setUp()
    {
        $this->configHelper = $this->createMock(ConfigHelper::class);
        $this->exportCustomerService = $this->createMock(ExportCustomerService::class);
        $this->observer = $this->createMock(Observer::class);
        $this->event = $this->createMock(Event::class);
        $this->customer = $this->createMock(Customer::class);

        $this->exportCustomerObserver = new ExportCustomerObserver(
            $this->configHelper,
            $this->exportCustomerService
        );
    }

    public function testExecuteApiDisabled()
    {
        $this->configHelper->expects($this->once())
            ->method('isApiEnabled')
            ->willReturn(false);

        $this->observer->expects($this->never())
            ->method('getEvent');

        $this->exportCustomerObserver->execute($this->observer);
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

        $this->exportCustomerService->expects($this->once())
            ->method('export')
            ->with($this->customer);

        $this->exportCustomerObserver->execute($this->observer);
    }
}
