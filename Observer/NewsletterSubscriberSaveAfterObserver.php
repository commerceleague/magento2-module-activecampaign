<?php
declare(strict_types=1);
/**
 */

namespace CommerceLeague\ActiveCampaign\Observer;

use CommerceLeague\ActiveCampaign\Service\Contact\CreateUpdateContactService;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Newsletter\Model\Subscriber;
use CommerceLeague\ActiveCampaign\Helper\Config as ConfigHelper;

/**
 * Class NewsletterSubscriberSaveAfterObserver
 */
class NewsletterSubscriberSaveAfterObserver implements ObserverInterface
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
     * @inheritDoc
     */
    public function execute(Observer $observer)
    {
        if (!$this->configHelper->isApiEnabled()) {
            return;
        }

        /** @var Subscriber $subscriber */
        $subscriber = $observer->getEvent()->getData('subscriber');
        $this->createUpdateContactService->executeWithSubscriber($subscriber);
    }
}
