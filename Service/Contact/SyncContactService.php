<?php
declare(strict_types=1);
/**
 */

namespace CommerceLeague\ActiveCampaign\Service\Contact;

use CommerceLeague\ActiveCampaign\Gateway\Request\ContactBuilder as ContactRequestBuilder;
use CommerceLeague\ActiveCampaign\MessageQueue\Topics;
use Magento\Customer\Model\Customer as MagentoCustomer;
use Magento\Framework\MessageQueue\PublisherInterface;
use Magento\Newsletter\Model\Subscriber;

class SyncContactService
{
    /**
     * @var ContactRequestBuilder
     */
    private $contactRequestBuilder;

    /**
     * @var PublisherInterface
     */
    private $publisher;

    /**
     * @param ContactRequestBuilder $contactRequestBuilder
     * @param PublisherInterface $publisher
     */
    public function __construct(
        ContactRequestBuilder $contactRequestBuilder,
        PublisherInterface $publisher
    ) {
        $this->contactRequestBuilder = $contactRequestBuilder;
        $this->publisher = $publisher;
    }

    /**
     * @param MagentoCustomer $magentoCustomer
     */
    public function syncWithMagentoCustomer(MagentoCustomer $magentoCustomer): void
    {
        $data = [
            'email' => $magentoCustomer->getData('email'),
            'request' => $this->contactRequestBuilder->buildWithMagentoCustomer($magentoCustomer)
        ];

        $this->publisher->publish(Topics::CONTACT_SYNC, json_encode($data));
    }

    /**
     * @param Subscriber $subscriber
     */
    public function syncWithSubscriber(Subscriber $subscriber): void
    {
        $data = [
            'email' => $subscriber->getEmail(),
            'request' => $this->contactRequestBuilder->buildWithSubscriber($subscriber)
        ];

        $this->publisher->publish(Topics::CONTACT_SYNC, json_encode($data));
    }
}