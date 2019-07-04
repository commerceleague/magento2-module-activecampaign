<?php
declare(strict_types=1);
/**
 */
namespace CommerceLeague\ActiveCampaign\Observer\Customer;

use CommerceLeague\ActiveCampaign\Helper\Config as ConfigHelper;
use CommerceLeague\ActiveCampaign\Service\Contact\SyncContactService;
use Magento\Customer\Model\Customer;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;

/**
 * Class SyncContactObserver
 */
class SyncContactObserver implements ObserverInterface
{
    /**
     * @var ConfigHelper
     */
    private $configHelper;

    /**
     * @var SyncContactService
     */
    private $syncContactService;

    /**
     * @param ConfigHelper $configHelper
     * @param SyncContactService $syncContactService
     */
    public function __construct(
        ConfigHelper $configHelper,
        SyncContactService $syncContactService
    ) {
        $this->configHelper = $configHelper;
        $this->syncContactService = $syncContactService;
    }

    /**
     * @param Observer $observer
     */
    public function execute(Observer $observer)
    {
        if (!$this->configHelper->isApiEnabled()) {
            return;
        }

        /** @var Customer $magentoCustomer */
        $magentoCustomer = $observer->getEvent()->getData('customer');

        $this->syncContactService->syncWithMagentoCustomer($magentoCustomer);
    }
}
