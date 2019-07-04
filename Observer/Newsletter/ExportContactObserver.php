<?php
declare(strict_types=1);
/**
 */

namespace CommerceLeague\ActiveCampaign\Observer\Newsletter;

use CommerceLeague\ActiveCampaign\Service\ExportContactService;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Newsletter\Model\Subscriber;
use CommerceLeague\ActiveCampaign\Helper\Config as ConfigHelper;

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
     * @inheritDoc
     */
    public function execute(Observer $observer)
    {
        if (!$this->configHelper->isApiEnabled()) {
            return;
        }

        /** @var Subscriber $subscriber */
        $subscriber = $observer->getEvent()->getData('subscriber');

        if ($subscriber->getData('customer_id')) {
            return;
        }

        $this->exportContactService->exportWithSubscriber($subscriber);
    }
}
