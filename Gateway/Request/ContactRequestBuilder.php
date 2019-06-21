<?php
declare(strict_types=1);

namespace CommerceLeague\ActiveCampaign\Gateway\Request;

use Magento\Customer\Model\Customer;

/**
 * Class ContactRequestBuilder
 */
class ContactRequestBuilder
{
    public function build(Customer $customer): array
    {
        return [
            'email' => $customer->getData('email'),
            'firstName' => $customer->getData('firstname'),
            'lastName' => $customer->getData('lastname')
        ];
    }
}
