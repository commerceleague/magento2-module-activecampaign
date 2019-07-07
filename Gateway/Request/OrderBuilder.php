<?php
declare(strict_types=1);
/**
 */

namespace CommerceLeague\ActiveCampaign\Gateway\Request;

use CommerceLeague\ActiveCampaign\Api\CustomerRepositoryInterface;
use CommerceLeague\ActiveCampaign\Helper\Config as ConfigHelper;
use Magento\Sales\Api\Data\OrderInterface as MagentoOrderInterface;
use Magento\Sales\Model\Order as MagentoOrder;

/**
 * Class OrderBuilder
 */
class OrderBuilder
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
     * @param MagentoOrderInterface|MagentoOrder $magentoOrder
     * @return array
     */
    public function build(MagentoOrderInterface $magentoOrder): array
    {
        $customer = $this->customerRepository->getByMagentoCustomerId($magentoOrder->getCustomerId());

        $request = [
            'externalid' => $magentoOrder->getId(),
            'source' => 1,
            'email' => $magentoOrder->getCustomerEmail(),
            'orderNumber' => $magentoOrder->getIncrementId(),
            'orderDate' => $magentoOrder->getCreatedAt(),
            'shippingMethod' => $magentoOrder->getShippingMethod(),
            'totalPrice' => $this->convertToCent((float)$magentoOrder->getGrandTotal()),
            'currency' => $magentoOrder->getBaseCurrencyCode(),
            'connectionid' => $this->configHelper->getConnectionId(),
            'customerid' => $customer->getActiveCampaignId(),
            'orderProducts' => []
        ];

        /** @var MagentoOrder\Item $magentoOrderItem */
        foreach ($magentoOrder->getAllVisibleItems() as $magentoOrderItem) {
            $request['orderProducts'][] = [
                'externalid' => $magentoOrderItem->getSku(),
                'name' => $magentoOrderItem->getName(),
                'price' => $this->convertToCent((float)$magentoOrderItem->getPriceInclTax()),
                'quantity' => (int)$magentoOrderItem->getQtyOrdered()
            ];
        }

        return $request;
    }

    /**
     * @param float $amount
     * @return int
     */
    private function convertToCent(float $amount): int
    {
        return (int)($amount * 100);
    }
}
