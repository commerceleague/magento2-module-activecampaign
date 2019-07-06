<?php
declare(strict_types=1);
/**
 */

namespace CommerceLeague\ActiveCampaign\Observer\Sales;

use CommerceLeague\ActiveCampaign\Helper\Config as ConfigHelper;
use CommerceLeague\ActiveCampaign\Service\ExportOrderService;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Sales\Model\Order as MagentoOrder;

/**
 * Class ExportOrderObserver
 */
class ExportOrderObserver implements ObserverInterface
{
    /**
     * @var ConfigHelper
     */
    private $configHelper;

    /**
     * @var ExportOrderService
     */
    private $exportOrderService;

    /**
     * @param ConfigHelper $configHelper
     * @param ExportOrderService $exportOrderService
     */
    public function __construct(
        ConfigHelper $configHelper,
        ExportOrderService $exportOrderService
    ) {
        $this->configHelper = $configHelper;
        $this->exportOrderService = $exportOrderService;
    }

    /**
     * @inheritDoc
     */
    public function execute(Observer $observer)
    {
        if (!$this->configHelper->isApiEnabled()) {
            return;
        }

        /** @var MagentoOrder $magentoOrder */
        $magentoOrder = $observer->getEvent()->getData('order');

        // do not export guest orders for now
        if ($magentoOrder->getStatus() !== MagentoOrder::STATE_COMPLETE || $magentoOrder->getCustomerIsGuest()) {
            return;
        }

        $this->exportOrderService->export($magentoOrder);
    }
}
