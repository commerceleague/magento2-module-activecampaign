<?php
declare(strict_types=1);
/**
 */

namespace CommerceLeague\ActiveCampaign\Gateway\Request;

use CommerceLeague\ActiveCampaign\Api\CustomerRepositoryInterface;
use CommerceLeague\ActiveCampaign\Helper\Config as ConfigHelper;
use Magento\Quote\Model\Quote;

/**
 * Class AbandonedCartRequestBuilder
 */
class AbandonedCartBuilder extends AbstractBuilder
{
    public function __construct(private readonly ConfigHelper $configHelper, private readonly CustomerRepositoryInterface $customerRepository)
    {
    }

    /**
     * @return array<string,mixed>
     * @throws \Exception
     */
    public function build(Quote $quote): array
    {
        $customerId = $quote->getData('customer_id');

        $request = [
            'externalcheckoutid' => $quote->getId(),
            'source' => 1,
            'email' => $quote->getData('customer_email'),
            'externalCreatedDate' => $this->formatDateTime($quote->getCreatedAt()),
            'externalUpdatedDate' => $this->formatDateTime($quote->getUpdatedAt() ?: $quote->getCreatedAt()),
            'abandonedDate' => $this->formatDateTime($quote->getUpdatedAt() ?: $quote->getCreatedAt()),
            'totalPrice' => $this->convertToCent((float)$quote->getData('grand_total')),
            'currency' => $quote->getData('base_currency_code'),
            'connectionid' => $this->configHelper->getConnectionId(),
            'customerid' => null,
            'orderProducts' => []
        ];

        // A guest abandoned cart has no customer_id and therefore no registered
        // ActiveCampaign customer; skip the lookup and leave customerid null.
        if (!empty($customerId)) {
            $customer = $this->customerRepository->getByMagentoCustomerId($customerId);
            $request['customerid'] = $customer->getActiveCampaignId();
        }

        foreach ($quote->getAllVisibleItems() as $quoteItem) {
            // Quote\Item::getProduct() is documented as non-null, but a deleted
            // product yields null at runtime; treat it defensively.
            /** @var \Magento\Catalog\Model\Product|null $product */
            $product = $quoteItem->getProduct();

            $request['orderProducts'][] = [
                'externalid' => $quoteItem->getSku(),
                'name' => $quoteItem->getName(),
                'price' => $this->convertToCent((float)$quoteItem->getPriceInclTax()),
                'quantity' => (int)$quoteItem->getQty(),
                'productUrl' => $product !== null ? $product->getProductUrl() : '',
                'sku' => $quoteItem->getSku(),
                'category' => $product !== null ? $this->buildCategoryNames($product) : '',
            ];
        }

        return $request;
    }
}
