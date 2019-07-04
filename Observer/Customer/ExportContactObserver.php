<?php
declare(strict_types=1);
/**
 */
namespace CommerceLeague\ActiveCampaign\Observer\Customer;

use CommerceLeague\ActiveCampaign\Helper\Config as ConfigHelper;
use CommerceLeague\ActiveCampaign\Service\ExportContactService;
use Magento\Customer\Model\Customer;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;

/**
 * Class ExportContactObserver
 */
class ExportContactObserver implements ObserverInterface
{
    /**
     * @var ConfigHelper
     */
    private $configHelper;

    /**
     * @var ExportContactService
     */
    private $exportContactService;

    /**
     * @param ConfigHelper $configHelper
     * @param ExportContactService $exportContactService
     */
    public function __construct(
        ConfigHelper $configHelper,
        ExportContactService $exportContactService
    ) {
        $this->configHelper = $configHelper;
        $this->exportContactService = $exportContactService;
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

        $this->exportContactService->exportWithMagentoCustomer($magentoCustomer);
    }
}
