<?php
declare(strict_types=1);
/**
 */

namespace CommerceLeague\ActiveCampaign\Gateway\Request;

use CommerceLeague\ActiveCampaign\Api\CustomerRepositoryInterface;
use CommerceLeague\ActiveCampaign\Helper\Config as ConfigHelper;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
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
     * @var TimezoneInterface
     */
    private $timezone;

    /**
     * @param ConfigHelper $configHelper
     * @param CustomerRepositoryInterface $customerRepository
     * @param TimezoneInterface $timezone
     */
    public function __construct(
        ConfigHelper $configHelper,
        CustomerRepositoryInterface $customerRepository,
        TimezoneInterface $timezone
    ) {
        $this->configHelper = $configHelper;
        $this->customerRepository = $customerRepository;
        $this->timezone = $timezone;
    }

    /**
     * @param MagentoOrderInterface|MagentoOrder $magentoOrder
     * @return array
     * @throws \Exception
     */
    public function build(MagentoOrderInterface $magentoOrder): array
    {
        $customer = $this->customerRepository->getByMagentoCustomerId($magentoOrder->getCustomerId());

        $request = [
            'externalid' => $magentoOrder->getId(),
            'source' => 1,
            'email' => $magentoOrder->getCustomerEmail(),
            'orderNumber' => $magentoOrder->getIncrementId(),
            'orderDate' => $this->formatDateTime($magentoOrder->getCreatedAt()),
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

    /**
     * @param string $date
     * @return string
     * @throws \Exception
     */
    private function formatDateTime(string $date): string
    {
        return (new \DateTime($date))->format(\DateTime::W3C);
    }
}
