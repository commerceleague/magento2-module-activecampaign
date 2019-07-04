<?php
declare(strict_types=1);
/**
 */

namespace CommerceLeague\ActiveCampaign\Test\Unit\Service;

use CommerceLeague\ActiveCampaign\MessageQueue\Topics;
use CommerceLeague\ActiveCampaign\Service\ExportCustomerService;
use Magento\Customer\Model\Customer;
use Magento\Framework\MessageQueue\PublisherInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use CommerceLeague\ActiveCampaign\Gateway\Request\CustomerBuilder as CustomerRequestBuilder;

class ExportCustomerServiceTest extends TestCase
{
    /**
     * @var MockObject|CustomerRequestBuilder
     */
    protected $customerRequestBuilder;

    /**
     * @var MockObject|PublisherInterface
     */
    protected $publisher;

    /**
     * @var ExportCustomerService
     */
    protected $exportCustomerService;

    protected function setUp()
    {
        $this->customerRequestBuilder = $this->createMock(CustomerRequestBuilder::class);
        $this->publisher = $this->createMock(PublisherInterface::class);
        $this->exportCustomerService = new ExportCustomerService(
            $this->customerRequestBuilder,
            $this->publisher
        );
    }

    public function testExport()
    {
        /** @var MockObject|Customer $magentoCustomer */
        $magentoCustomer = $this->createMock(Customer::class);

        $magentoCustomerId = 123;
        $request = ['request'];

        $magentoCustomer->expects($this->once())
            ->method('getId')
            ->willReturn($magentoCustomerId);

        $this->customerRequestBuilder->expects($this->once())
            ->method('build')
            ->with($magentoCustomer)
            ->willReturn($request);

        $this->publisher->expects($this->once())
            ->method('publish')
            ->with(
                Topics::CUSTOMER_EXPORT,
                json_encode(['magento_customer_id' => $magentoCustomerId, 'request' => $request])
            );

        $this->exportCustomerService->export($magentoCustomer);
    }
}

