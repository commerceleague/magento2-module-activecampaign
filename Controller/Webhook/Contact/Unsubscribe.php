<?php
declare(strict_types=1);
/**
 */

namespace CommerceLeague\ActiveCampaign\Controller\Webhook\Contact;

use CommerceLeague\ActiveCampaign\Controller\AbstractWebhook;
use CommerceLeague\ActiveCampaign\Helper\Config as ConfigHelper;
use CommerceLeague\ActiveCampaign\Logger\Logger;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Controller\Result\RawFactory as RawResultFactory;
use Magento\Framework\Exception\LocalizedException;
use Magento\Newsletter\Model\SubscriberFactory;
use Magento\Newsletter\Model\Subscriber;

/**
 * Class Unsubscribe
 */
class Unsubscribe extends AbstractWebhook
{
    /**
     * @var SubscriberFactory
     */
    private $subscriberFactory;

    /**
     * @var Logger
     */
    private $logger;

    /**
     * @param Context $context
     * @param ConfigHelper $configHelper
     * @param RawResultFactory $rawResultFactory
     * @param SubscriberFactory $subscriberFactory
     * @param Logger $logger
     */
    public function __construct(
        Context $context,
        ConfigHelper $configHelper,
        RawResultFactory $rawResultFactory,
        SubscriberFactory $subscriberFactory,
        Logger $logger
    ) {
        parent::__construct($context, $configHelper, $rawResultFactory);
        $this->subscriberFactory = $subscriberFactory;
        $this->logger = $logger;
    }

    /**
     * @inheritDoc
     */
    public function execute()
    {
        $params = $this->getRequest()->getParams();

        if (!isset($params['contact']) || !(isset($params['contact']['email']))) {
            $this->logger->error(__('Invalid webhook params received'));
            return;
        }

        $email = $params['contact']['email'];

        /** @var Subscriber $subscriber */
        $subscriber = $this->subscriberFactory->create();
        $subscriber->loadByEmail($email);

        if (!$subscriber->getId()) {
            $this->logger->error(__('Unable to find subscriber with email "%s"', $email));
            return;
        }

        try {
            $subscriber->unsubscribe();
        } catch (LocalizedException $e) {
            $this->logger->error($e->getMessage());
            return;
        }
    }
}
