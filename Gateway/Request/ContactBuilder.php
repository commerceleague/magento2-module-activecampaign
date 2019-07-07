<?php
declare(strict_types=1);
/**
 */

namespace CommerceLeague\ActiveCampaign\Gateway\Request;

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
        return [
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
        return [
            'email' => $subscriber->getEmail()
        ];
    }
}
