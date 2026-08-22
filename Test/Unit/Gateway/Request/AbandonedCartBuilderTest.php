<?php
declare(strict_types=1);
/**
 */

namespace CommerceLeague\ActiveCampaign\Test\Unit\Gateway\Request;

use CommerceLeague\ActiveCampaign\Api\CustomerRepositoryInterface;
use CommerceLeague\ActiveCampaign\Gateway\Request\AbandonedCartBuilder;
use CommerceLeague\ActiveCampaign\Helper\Config as ConfigHelper;
use CommerceLeague\ActiveCampaign\Test\Unit\AbstractTestCase;
use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\ResourceModel\Category\Collection as CategoryCollection;
use Magento\Framework\DataObject;
use Magento\Quote\Model\Quote;
use Magento\Quote\Model\Quote\Item as QuoteItem;
use PHPUnit\Framework\MockObject\MockObject;

class AbandonedCartBuilderTest extends AbstractTestCase
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
     * @var MockObject|Quote
     */
    protected $quote;

    /**
     * @var AbandonedCartBuilder
     */
    protected $abandonedCartBuilder;

    protected function setUp(): void
    {
        $this->configHelper = $this->createMock(ConfigHelper::class);
        $this->customerRepository = $this->createMock(CustomerRepositoryInterface::class);
        $this->quote = $this->createMock(Quote::class);

        $this->abandonedCartBuilder = new AbandonedCartBuilder(
            $this->configHelper,
            $this->customerRepository
        );
    }

    /**
     * @param array<string,mixed> $data
     */
    private function configureQuote(array $data): void
    {
        $this->quote->method('getData')->willReturnCallback(
            static fn (string $key) => $data[$key] ?? null
        );
        $this->quote->method('getId')->willReturn(123);
        $this->quote->method('getCreatedAt')->willReturn('2026-01-01 00:00:00');
        $this->quote->method('getUpdatedAt')->willReturn('2026-01-02 00:00:00');
        $this->configHelper->method('getConnectionId')->willReturn('1');
    }

    public function testBuildGuestCartWithNullCustomerIdDoesNotFatal()
    {
        $this->configureQuote([
            'customer_id'        => null,
            'customer_email'     => 'guest@example.com',
            'grand_total'        => 10.0,
            'base_currency_code' => 'EUR',
        ]);

        // No registered-customer lookup for a guest cart.
        $this->customerRepository->expects($this->never())
            ->method('getByMagentoCustomerId');

        $this->quote->method('getAllVisibleItems')->willReturn([]);

        $request = $this->abandonedCartBuilder->build($this->quote);

        $this->assertNull($request['customerid']);
    }

    public function testBuildWithDeletedProductUsesEmptyProductUrl()
    {
        $this->configureQuote([
            'customer_id'        => null,
            'customer_email'     => 'guest@example.com',
            'grand_total'        => 10.0,
            'base_currency_code' => 'EUR',
        ]);

        // Quote item whose product has been deleted: getProduct() returns null.
        // getPriceInclTax is a magic getter on Quote\Item, so it must be added.
        $quoteItem = $this->getMockBuilder(QuoteItem::class)
            ->disableOriginalConstructor()
            ->addMethods(['getPriceInclTax'])
            ->onlyMethods(['getSku', 'getName', 'getQty', 'getProduct'])
            ->getMock();
        $quoteItem->method('getSku')->willReturn('SKU-1');
        $quoteItem->method('getName')->willReturn('Product 1');
        $quoteItem->method('getPriceInclTax')->willReturn(10.0);
        $quoteItem->method('getQty')->willReturn(1.0);
        $quoteItem->method('getProduct')->willReturn(null);

        $this->quote->method('getAllVisibleItems')->willReturn([$quoteItem]);

        $request = $this->abandonedCartBuilder->build($this->quote);

        $this->assertCount(1, $request['orderProducts']);
        $this->assertSame('', $request['orderProducts'][0]['productUrl']);
        $this->assertSame('SKU-1', $request['orderProducts'][0]['externalid']);
        $this->assertSame('', $request['orderProducts'][0]['category']);
        $this->assertSame('SKU-1', $request['orderProducts'][0]['sku']);
    }

    public function testBuildAddsSkuAndCategoryToProductLine()
    {
        $this->configureQuote([
            'customer_id'        => null,
            'customer_email'     => 'guest@example.com',
            'grand_total'        => 10.0,
            'base_currency_code' => 'EUR',
        ]);

        $category = new DataObject(['name' => 'Naturkosmetik']);
        $categoryCollection = $this->createMock(CategoryCollection::class);
        $categoryCollection->method('addAttributeToSelect')->willReturnSelf();
        $categoryCollection->method('getIterator')->willReturn(new \ArrayIterator([$category]));

        $product = $this->createMock(Product::class);
        $product->method('getProductUrl')->willReturn('https://shop.example/product-1.html');
        $product->method('getCategoryCollection')->willReturn($categoryCollection);

        $quoteItem = $this->getMockBuilder(QuoteItem::class)
            ->disableOriginalConstructor()
            ->addMethods(['getPriceInclTax'])
            ->onlyMethods(['getSku', 'getName', 'getQty', 'getProduct'])
            ->getMock();
        $quoteItem->method('getSku')->willReturn('SKU-1');
        $quoteItem->method('getName')->willReturn('Product 1');
        $quoteItem->method('getPriceInclTax')->willReturn(10.0);
        $quoteItem->method('getQty')->willReturn(1.0);
        $quoteItem->method('getProduct')->willReturn($product);

        $this->quote->method('getAllVisibleItems')->willReturn([$quoteItem]);

        $request = $this->abandonedCartBuilder->build($this->quote);

        $this->assertSame('SKU-1', $request['orderProducts'][0]['sku']);
        $this->assertSame('Naturkosmetik', $request['orderProducts'][0]['category']);
    }
}
