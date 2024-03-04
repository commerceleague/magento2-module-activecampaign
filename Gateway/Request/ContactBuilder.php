<?php
declare(strict_types=1);
/**
 */

namespace CommerceLeague\ActiveCampaign\Gateway\Request;

use CommerceLeague\ActiveCampaign\Api\Data\ContactInterface;
use CommerceLeague\ActiveCampaign\Helper\Contants;
use Magento\Customer\Api\Data\CustomerInterface as MagentoCustomerInterface;
use Magento\Newsletter\Model\Subscriber;

/**
 * Class ContactBuilder
 */
class ContactBuilder
{

    public function buildWithMagentoCustomer(MagentoCustomerInterface $magentoCustomer): array
    {
        return [
            'status'    => Contants::CONTACT_STATUS_ACTIVE,
            'email'     => $magentoCustomer->getEmail(),
            'firstName' => $magentoCustomer->getFirstname(),
            'lastName'  => $magentoCustomer->getLastname()
        ];
    }

    public function buildWithSubscriber(Subscriber $subscriber): array
    {
        return [
            'status' => Contants::CONTACT_STATUS_ACTIVE,
            'email'  => $subscriber->getEmail()
        ];
    }

    public function buildWithGuestContact(ContactInterface $contact, string $firstname, string $lastname): array
    {
        return [
            'status'    => Contants::CONTACT_STATUS_ACTIVE,
            'email'     => $contact->getEmail(),
            'firstName' => $firstname,
            'lastName'  => $lastname
        ];
    }
}
