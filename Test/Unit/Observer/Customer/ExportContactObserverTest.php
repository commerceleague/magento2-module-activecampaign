<?php
declare(strict_types=1);
/**
 */

namespace CommerceLeague\ActiveCampaign\Test\Observer\Customer;

use CommerceLeague\ActiveCampaign\Helper\Config as ConfigHelper;
use CommerceLeague\ActiveCampaign\MessageQueue\Topics;
use CommerceLeague\ActiveCampaign\Observer\Customer\ExportContactObserver;
use Magento\Customer\Model\Customer as MagentoCustomer;
use Magento\Framework\Event;
use Magento\Framework\Event\Observer;
use Magento\Framework\MessageQueue\PublisherInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class ExportContactObserverTest extends TestCase
{
    /**
     * @var MockObject|ConfigHelper
     */
    protected $configHelper;

    /**
     * @var MockObject|PublisherInterface
     */
    protected $publisher;

    /**
     * @var MockObject|Observer
     */
    protected $observer;

    /**
     * @var MockObject|Event
     */
    protected $event;

    /**
     * @var MockObject|MagentoCustomer
     */
    protected $magentoCustomer;

    /**
     * @var ExportContactObserver
     */
    protected $exportContactObserver;

    protected function setUp()
    {
        $this->configHelper = $this->createMock(ConfigHelper::class);
        $this->publisher = $this->createMock(PublisherInterface::class);
        $this->observer = $this->createMock(Observer::class);
        $this->event = $this->createMock(Event::class);
        $this->magentoCustomer = $this->createMock(MagentoCustomer::class);

        $this->exportContactObserver = new ExportContactObserver(
            $this->configHelper,
            $this->publisher
        );
    }

    public function testExecuteApiDisabled()
    {
        $this->configHelper->expects($this->once())
            ->method('isEnabled')
            ->willReturn(false);

        $this->observer->expects($this->never())
            ->method('getEvent');

        $this->exportContactObserver->execute($this->observer);
    }

    public function testExecuteContactExportDisabled()
    {
        $this->configHelper->expects($this->once())
            ->method('isEnabled')
            ->willReturn(true);

        $this->configHelper->expects($this->once())
            ->method('isContactExportEnabled')
            ->willReturn(false);

        $this->observer->expects($this->never())
            ->method('getEvent');

        $this->exportContactObserver->execute($this->observer);
    }

    public function testExecute()
    {
        $magentoCustomerId = 123;

        $this->configHelper->expects($this->once())
            ->method('isEnabled')
            ->willReturn(true);

        $this->configHelper->expects($this->once())
            ->method('isContactExportEnabled')
            ->willReturn(true);

        $this->observer->expects($this->once())
            ->method('getEvent')
            ->willReturn($this->event);

        $this->event->expects($this->once())
            ->method('getData')
            ->with('customer')
            ->willReturn($this->magentoCustomer);

        $this->magentoCustomer->expects($this->once())
            ->method('getId')
            ->willReturn($magentoCustomerId);

        $this->publisher->expects($this->once())
            ->method('publish')
            ->with(
                Topics::CUSTOMER_CONTACT_EXPORT,
                json_encode(['magento_customer_id' => $magentoCustomerId])
            );

        $this->exportContactObserver->execute($this->observer);
    }
}
