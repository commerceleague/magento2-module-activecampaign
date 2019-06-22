<?php
/**
 */

namespace CommerceLeague\ActiveCampaign\Test\Unit\Observer\Customer;

use CommerceLeague\ActiveCampaign\Helper\Config as ConfigHelper;
use CommerceLeague\ActiveCampaign\Observer\CustomerSaveAfterObserver;
use CommerceLeague\ActiveCampaign\Service\Contact\CreateUpdateContactService;
use Magento\Customer\Model\Customer;
use Magento\Framework\Event;
use Magento\Framework\Event\Observer;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Class CustomerSaveAfterObserverTest
 */
class CustomerSaveAfterObserverTest extends TestCase
{
    /**
     * @var MockObject|ConfigHelper
     */
    protected $configHelper;

    /**
     * @var MockObject|CreateUpdateContactService
     */
    protected $createUpdateContactService;

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
     * @var CustomerSaveAfterObserver
     */
    protected $createUpdateContactObserver;

    protected function setUp()
    {
        $this->configHelper = $this->createMock(ConfigHelper::class);
        $this->createUpdateContactService = $this->createMock(CreateUpdateContactService::class);
        $this->observer = $this->createMock(Observer::class);
        $this->event = $this->createMock(Event::class);
        $this->customer = $this->createMock(Customer::class);

        $this->createUpdateContactObserver = new CustomerSaveAfterObserver(
            $this->configHelper,
            $this->createUpdateContactService
        );
    }

    public function testExecuteApiNotEnabled()
    {
        $this->configHelper->expects($this->once())
            ->method('isApiEnabled')
            ->willReturn(false);

        $this->observer->expects($this->never())
            ->method('getEvent');

        $this->createUpdateContactObserver->execute($this->observer);
    }

    public function testExecuteApiEnabled()
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

        $this->createUpdateContactService->expects($this->once())
            ->method('execute')
            ->with($this->customer);

        $this->createUpdateContactObserver->execute($this->observer);
    }
}