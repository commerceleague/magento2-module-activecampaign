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
    /**
     * @var ConfigHelper
     */
    private $configHelper;

    /**
     * @var CustomerRepositoryInterface
     */
    private $customerRepository;

    /**
     * @param ConfigHelper $configHelper
     * @param CustomerRepositoryInterface $customerRepository
     */
    public function __construct(
        ConfigHelper $configHelper,
        CustomerRepositoryInterface $customerRepository
    ) {
        $this->configHelper = $configHelper;
        $this->customerRepository = $customerRepository;
    }

    /**
     * @param Quote $quote
     * @return array
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
                'price' => $this->convertToCent((float)$quoteItem->getPriceInclTax()),
                'quantity' => (int)$quoteItem->getQty(),
                'productUrl' => $quoteItem->getProduct()->getProductUrl(),
            ];
        }

        return $request;
    }
}
