<?php
declare(strict_types=1);
/**
 */

namespace CommerceLeague\ActiveCampaign\Observer\Customer;

use CommerceLeague\ActiveCampaign\Helper\Config as ConfigHelper;
use CommerceLeague\ActiveCampaign\Service\ExportCustomerService;
use Magento\Customer\Model\Customer as MagentoCustomer;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;

/**
 * Class ExportCustomerObserver
 */
class ExportCustomerObserver implements ObserverInterface
{
    /**
     * @var ConfigHelper
     */
    private $configHelper;

    /**
     * @var ExportCustomerService
     */
    private $exportCustomerService;

    /**
     * @param ConfigHelper $configHelper
     * @param ExportCustomerService $exportCustomerService
     */
    public function __construct(
        ConfigHelper $configHelper,
        ExportCustomerService $exportCustomerService
    ) {
        $this->configHelper = $configHelper;
        $this->exportCustomerService = $exportCustomerService;
    }

    /**
     * @inheritDoc
     */
    public function execute(Observer $observer)
    {
        if (!$this->configHelper->isApiEnabled()) {
            return;
        }

        /** @var MagentoCustomer $magentoCustomer */
        $magentoCustomer = $observer->getEvent()->getData('customer');

        $this->exportCustomerService->export($magentoCustomer);
    }
}
