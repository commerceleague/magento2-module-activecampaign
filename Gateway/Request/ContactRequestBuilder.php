<?php
declare(strict_types=1);

namespace CommerceLeague\ActiveCampaign\Gateway\Request;

use Magento\Customer\Model\Customer;
use Magento\Newsletter\Model\Subscriber;

/**
 * Class ContactRequestBuilder
 */
class ContactRequestBuilder
{
    /**
     * @param Customer $customer
     * @return array
     */
    public function buildWithCustomer(Customer $customer): array
    {
        return [
            'email' => $customer->getData('email'),
            'firstName' => $customer->getData('firstname'),
            'lastName' => $customer->getData('lastname')
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
