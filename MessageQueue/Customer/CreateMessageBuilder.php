<?php
declare(strict_types=1);
/**
 */

namespace CommerceLeague\ActiveCampaign\MessageQueue\Customer;

use CommerceLeague\ActiveCampaign\Api\Data\CustomerInterface;
use CommerceLeague\ActiveCampaign\Helper\Config as ConfigHelper;
use Magento\Customer\Model\Customer as MagentoCustomer;

class CreateMessageBuilder
{
    /**
     * @var ConfigHelper
     */
    private $configHelper;

    /**
     * @var CreateMessageFactory
     */
    private $createMessageFactory;

    /**
     * @param ConfigHelper $configHelper
     * @param CreateMessageFactory $createMessageFactory
     */
    public function __construct(
        ConfigHelper $configHelper,
        CreateMessageFactory $createMessageFactory
    ) {
        $this->createMessageFactory = $createMessageFactory;
        $this->configHelper = $configHelper;
    }

    /**
     * @param CustomerInterface $customer
     * @param MagentoCustomer $magentoCustomer
     * @return CreateMessage
     */
    public function build(CustomerInterface $customer, MagentoCustomer $magentoCustomer): CreateMessage
    {
        $request = [
            'connectionid' => $this->configHelper->getConnectionId(),
            'externalid' => $magentoCustomer->getId(),
            'email' => $magentoCustomer->getData('email'),
            'acceptsMarketing' => 1 // TODO::check how this value could be set
        ];

        /** @var CreateMessage $message */
        $message = $this->createMessageFactory->create();

        $message->setEntityId((int)$customer->getId())
            ->setSerializedRequest(json_encode($request));

        return $message;
    }
}
