<?php
declare(strict_types=1);
/**
 */

namespace CommerceLeague\ActiveCampaign\Observer\Customer;

use CommerceLeague\ActiveCampaign\Helper\Config as ConfigHelper;
use CommerceLeague\ActiveCampaign\Service\Customer\SyncCustomerService;
use Magento\Customer\Model\Customer as MagentoCustomer;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;

/**
 * Class SyncCustomerObserver
 */
class SyncCustomerObserver implements ObserverInterface
{
    /**
     * @var ConfigHelper
     */
    private $configHelper;

    /**
     * @var SyncCustomerService
     */
    private $syncCustomerService;

    /**
     * @param ConfigHelper $configHelper
     * @param SyncCustomerService $syncCustomerService
     */
    public function __construct(
        ConfigHelper $configHelper,
        SyncCustomerService $syncCustomerService
    ) {
        $this->configHelper = $configHelper;
        $this->syncCustomerService = $syncCustomerService;
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

        $this->syncCustomerService->sync($magentoCustomer);
    }
}
