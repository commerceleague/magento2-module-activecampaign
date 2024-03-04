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
        $customer = $this->customerRepository->getByMagentoCustomerId($quote->getData('customer_id'));

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
            'customerid' => $customer->getActiveCampaignId(),
            'orderProducts' => []
        ];

        foreach ($quote->getAllVisibleItems() as $quoteItem) {
            $request['orderProducts'][] = [
                'externalid' => $quoteItem->getSku(),
                'name' => $quoteItem->getName(),
                'price' => $this->convertToCent($quoteItem->getPriceInclTax()),
                'quantity' => (int)$quoteItem->getQty(),
                'productUrl' => $quoteItem->getProduct()->getProductUrl(),
            ];
        }

        return $request;
    }
}
