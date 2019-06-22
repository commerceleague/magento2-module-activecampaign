<?php
declare(strict_types=1);
/**
 */
namespace CommerceLeague\ActiveCampaign\Observer;

use CommerceLeague\ActiveCampaign\Helper\Config as ConfigHelper;
use CommerceLeague\ActiveCampaign\Service\Contact\CreateUpdateContactService;
use Magento\Customer\Model\Customer;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;

/**
 * Class CustomerSaveAfterObserver
 */
class CustomerSaveAfterObserver implements ObserverInterface
{
    /**
     * @var ConfigHelper
     */
    private $configHelper;

    /**
     * @var CreateUpdateContactService
     */
    private $createUpdateContactService;

    /**
     * @param ConfigHelper $configHelper
     * @param CreateUpdateContactService $createUpdateContactService
     */
    public function __construct(
        ConfigHelper $configHelper,
        CreateUpdateContactService $createUpdateContactService
    ) {
        $this->configHelper = $configHelper;
        $this->createUpdateContactService = $createUpdateContactService;
    }

    /**
     * @param Observer $observer
     */
    public function execute(Observer $observer)
    {
        if (!$this->configHelper->isApiEnabled()) {
            return;
        }

        /** @var Customer $customer */
        $customer = $observer->getEvent()->getData('customer');
        $this->createUpdateContactService->execute($customer);
    }
}
