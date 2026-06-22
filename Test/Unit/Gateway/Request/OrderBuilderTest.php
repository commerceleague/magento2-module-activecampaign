<?php
declare(strict_types=1);
/**
 */

namespace CommerceLeague\ActiveCampaign\Test\Unit\Gateway\Request;

use CommerceLeague\ActiveCampaign\Api\CustomerRepositoryInterface;
use CommerceLeague\ActiveCampaign\Api\Data\CustomerInterface;
use CommerceLeague\ActiveCampaign\Api\GuestCustomerRepositoryInterface;
use CommerceLeague\ActiveCampaign\Gateway\Request\OrderBuilder;
use CommerceLeague\ActiveCampaign\Helper\Config as ConfigHelper;
use CommerceLeague\ActiveCampaign\Test\Unit\AbstractTestCase;
use Magento\Sales\Model\Order as MagentoOrder;
use Magento\Sales\Model\Order\Item as MagentoOrderItem;
use PHPUnit\Framework\MockObject\MockObject;

class OrderBuilderTest extends AbstractTestCase
{

    /**
     * @var MockObject|ConfigHelper
     */
    protected $configHelper;

    /**
     * @var MockObject|CustomerRepositoryInterface
     */
    protected $customerRepository;

    /**
     * @var MockObject|GuestCustomerRepositoryInterface
     */
    protected $guestCustomerRepository;

    /**
     * @var MockObject|MagentoOrder
     */
    protected $magentoOrder;

    /**
     * @var OrderBuilder
     */
    protected $orderBuilder;

    protected function setUp(): void
    {
        $this->configHelper = $this->createMock(ConfigHelper::class);
        $this->customerRepository = $this->createMock(CustomerRepositoryInterface::class);
        $this->guestCustomerRepository = $this->createMock(GuestCustomerRepositoryInterface::class);
        $this->magentoOrder = $this->createMock(MagentoOrder::class);

        $this->orderBuilder = new OrderBuilder(
            $this->configHelper,
            $this->customerRepository,
            $this->guestCustomerRepository
        );
    }

    public function testBuildWithDeletedProductUsesEmptyProductUrl()
    {
        $customer = $this->createMock(CustomerInterface::class);
        $customer->method('getActiveCampaignId')->willReturn(999);

        $this->magentoOrder->method('getCustomerIsGuest')->willReturn(false);
        $this->magentoOrder->method('getCustomerId')->willReturn(42);
        $this->customerRepository->expects($this->once())
            ->method('getByMagentoCustomerId')
            ->with(42)
            ->willReturn($customer);

        $this->configHelper->method('getConnectionId')->willReturn('1');
        $this->magentoOrder->method('getId')->willReturn(123);
        $this->magentoOrder->method('getCustomerEmail')->willReturn('john@example.com');
        $this->magentoOrder->method('getIncrementId')->willReturn('000000123');
        $this->magentoOrder->method('getCreatedAt')->willReturn('2026-01-01 00:00:00');
        $this->magentoOrder->method('getShippingMethod')->willReturn('flatrate');
        $this->magentoOrder->method('getGrandTotal')->willReturn(10.0);
        $this->magentoOrder->method('getBaseCurrencyCode')->willReturn('EUR');

        // Order item whose product has been deleted: getProduct() returns null.
        $orderItem = $this->createMock(MagentoOrderItem::class);
        $orderItem->method('getSku')->willReturn('SKU-1');
        $orderItem->method('getName')->willReturn('Product 1');
        $orderItem->method('getPriceInclTax')->willReturn(10.0);
        $orderItem->method('getQtyOrdered')->willReturn(1.0);
        $orderItem->method('getProduct')->willReturn(null);

        $this->magentoOrder->method('getAllVisibleItems')->willReturn([$orderItem]);

        $request = $this->orderBuilder->build($this->magentoOrder);

        $this->assertCount(1, $request['orderProducts']);
        $this->assertSame('', $request['orderProducts'][0]['productUrl']);
        $this->assertSame('SKU-1', $request['orderProducts'][0]['externalid']);
    }
}
