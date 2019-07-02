<?php
declare(strict_types=1);
/**
 */

namespace CommerceLeague\ActiveCampaign\MessageQueue\Contact;

use CommerceLeague\ActiveCampaign\Api\Data\ContactInterface;
use Magento\Customer\Model\Customer;
use Magento\Newsletter\Model\Subscriber;

/**
 * Class CreateUpdateMessageBuilder
 */
class CreateUpdateMessageBuilder
{
    /**
     * @var CreateUpdateMessageFactory
     */
    private $createUpdateMessageFactory;

    /**
     * @param CreateUpdateMessageFactory $createUpdateMessageFactory
     */
    public function __construct(CreateUpdateMessageFactory $createUpdateMessageFactory)
    {
        $this->createUpdateMessageFactory = $createUpdateMessageFactory;
    }

    /**
     * @param ContactInterface $contact
     * @param Customer $customer
     * @return CreateUpdateMessage
     */
    public function buildWithCustomer(ContactInterface $contact, Customer $customer): CreateUpdateMessage
    {
        $request = [
            'email' => $customer->getData('email'),
            'firstName' => $customer->getData('firstname'),
            'lastName' => $customer->getData('lastname')
        ];

        /** @var CreateUpdateMessage $message */
        $message = $this->createUpdateMessageFactory->create();

        $message->setEntityId((int)$contact->getId())
            ->setSerializedRequest(json_encode($request));

        return $message;
    }

    /**
     * @param ContactInterface $contact
     * @param Subscriber $subscriber
     * @return CreateUpdateMessage
     */
    public function buildWithSubscriber(ContactInterface $contact, Subscriber $subscriber): CreateUpdateMessage
    {
        $request =  [
            'email' => $subscriber->getEmail()
        ];

        /** @var CreateUpdateMessage $message */
        $message = $this->createUpdateMessageFactory->create();

        $message->setEntityId((int)$contact->getId())
            ->setSerializedRequest(json_encode($request));

        return $message;
    }
}
