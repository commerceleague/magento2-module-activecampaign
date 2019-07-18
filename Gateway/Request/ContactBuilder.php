<?php
declare(strict_types=1);
/**
 */

namespace CommerceLeague\ActiveCampaign\Gateway\Request;

use CommerceLeague\ActiveCampaign\Helper\Contants;
use Magento\Customer\Api\Data\CustomerInterface as MagentoCustomerInterface;
use Magento\Newsletter\Model\Subscriber;

/**
 * Class ContactBuilder
 */
class ContactBuilder
{
    /**
     * @param MagentoCustomerInterface $magentoCustomer
     * @return array
     */
    public function buildWithMagentoCustomer(MagentoCustomerInterface $magentoCustomer): array
    {
        $isSubscribed = $magentoCustomer->getExtensionAttributes()->getIsSubscribed();

        return [
            'status' => $isSubscribed ? Contants::CONTACT_STATUS_ACTIVE : Contants::CONTACT_STATUS_UNSUBSCRIBED,
            'email' => $magentoCustomer->getEmail(),
            'firstName' => $magentoCustomer->getFirstname(),
            'lastName' => $magentoCustomer->getLastname()
        ];
    }

    /**
     * @param Subscriber $subscriber
     * @return array
     */
    public function buildWithSubscriber(Subscriber $subscriber): array
    {
        $isSubscribed = $subscriber->isSubscribed();

        return [
            'status' => $isSubscribed ? Contants::CONTACT_STATUS_ACTIVE : Contants::CONTACT_STATUS_UNSUBSCRIBED,
            'email' => $subscriber->getEmail()
        ];
    }
}
