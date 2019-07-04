<?php
declare(strict_types=1);
/**
 */

namespace CommerceLeague\ActiveCampaign\Observer\Newsletter;

use CommerceLeague\ActiveCampaign\MessageQueue\Topics;
use CommerceLeague\ActiveCampaign\Service\Contact\SyncContactService;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Newsletter\Model\Subscriber;
use CommerceLeague\ActiveCampaign\Helper\Config as ConfigHelper;

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

        $this->syncContactService->syncWithSubscriber($subscriber);
    }
}
