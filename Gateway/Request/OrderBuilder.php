<?php
declare(strict_types=1);
/**
 */

namespace CommerceLeague\ActiveCampaign\Gateway\Request;

use CommerceLeague\ActiveCampaign\Api\CustomerRepositoryInterface;
use CommerceLeague\ActiveCampaign\Api\GuestCustomerRepositoryInterface;
use CommerceLeague\ActiveCampaign\Helper\Config as ConfigHelper;
use Exception;
use Magento\Sales\Api\Data\OrderInterface as MagentoOrderInterface;
use Magento\Sales\Model\Order as MagentoOrder;

/**
 * Class OrderBuilder
 */
class OrderBuilder extends AbstractBuilder
{

    public function __construct(private readonly ConfigHelper $configHelper, private readonly CustomerRepositoryInterface $customerRepository, private readonly GuestCustomerRepositoryInterface $guestCustomerRepository)
    {
    }

    /**
     * @param MagentoOrderInterface|MagentoOrder $magentoOrder
     *
     * @return array
     * @throws Exception
     */
    public function build(MagentoOrderInterface $magentoOrder): array
    {
        if ($magentoOrder->getCustomerIsGuest()) {
            $customer = $this->guestCustomerRepository->getByEmail($magentoOrder->getCustomerEmail());
        } else {
            $customer = $this->customerRepository->getByMagentoCustomerId($magentoOrder->getCustomerId());
        }

        $request = [
            'externalid'     => $magentoOrder->getId(),
            'source'         => 1,
            'email'          => $magentoOrder->getCustomerEmail(),
            'orderNumber'    => $magentoOrder->getIncrementId(),
            'orderDate'      => $this->formatDateTime($magentoOrder->getCreatedAt()),
            'shippingMethod' => $magentoOrder->getShippingMethod(),
            'totalPrice'     => $this->convertToCent((float)$magentoOrder->getGrandTotal()),
            'currency'       => $magentoOrder->getBaseCurrencyCode(),
            'connectionid'   => $this->configHelper->getConnectionId(),
            'customerid'     => $customer->getActiveCampaignId(),
            'orderProducts'  => []
        ];

        /** @var MagentoOrder\Item $magentoOrderItem */
        foreach ($magentoOrder->getAllVisibleItems() as $magentoOrderItem) {
            $request['orderProducts'][] = [
                'externalid' => $magentoOrderItem->getSku(),
                'name'       => $magentoOrderItem->getName(),
                'price'      => $this->convertToCent((float)$magentoOrderItem->getPriceInclTax()),
                'quantity'   => (int)$magentoOrderItem->getQtyOrdered(),
                'productUrl' => $magentoOrderItem->getProduct()->getProductUrl(),
            ];
        }

        return $request;
    }
}
