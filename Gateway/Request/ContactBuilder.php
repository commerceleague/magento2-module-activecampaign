<?php
declare(strict_types=1);
/**
 */

namespace CommerceLeague\ActiveCampaign\Gateway\Request;

use Magento\Customer\Model\Customer as MagentoCustomer;
use Magento\Newsletter\Model\Subscriber;

/**
 * Class ContactBuilder
 */
class ContactBuilder
{
    /**
     * @param MagentoCustomer $magentoCustomer
     * @return array
     */
    public function buildWithMagentoCustomer(MagentoCustomer $magentoCustomer): array
    {
        return [
            'email' => $magentoCustomer->getData('email'),
            'firstName' => $magentoCustomer->getData('firstname'),
            'lastName' => $magentoCustomer->getData('lastname')
        ];
    }

    /**
     * @param Subscriber $subscriber
     * @return array
     */
    public function buildWithSubscriber(Subscriber $subscriber): array
    {
        return [
            'email' => $subscriber->getEmail()
        ];
    }
}
