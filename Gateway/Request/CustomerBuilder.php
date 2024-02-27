<?php
declare(strict_types=1);
/**
 */

namespace CommerceLeague\ActiveCampaign\Gateway\Request;

use CommerceLeague\ActiveCampaign\Api\Data\GuestCustomerInterface;
use CommerceLeague\ActiveCampaign\Helper\Config as ConfigHelper;
use CommerceLeague\ActiveCampaign\Helper\Contants;
use Magento\Customer\Api\Data\CustomerInterface as MagentoCustomerInterface;

/**
 * Class CustomerBuilder
 */
class CustomerBuilder
{

    public function __construct(private readonly ConfigHelper $configHelper)
    {
    }

    /**
     * @return array
     */
    public function build(MagentoCustomerInterface $magentoCustomer): array
    {
        return [
            'connectionid'     => $this->configHelper->getConnectionId(),
            'externalid'       => $magentoCustomer->getId(),
            'email'            => $magentoCustomer->getEmail(),
            'acceptsMarketing' => Contants::CONTACT_STATUS_ACTIVE
        ];
    }

    /**
     * @return array
     */
    public function buildWithGuest(GuestCustomerInterface $guestCustomer): array
    {
        return [
            'connectionid'     => $this->configHelper->getConnectionId(),
            'externalid'       => 'guest-' . $guestCustomer->getId(),
            'email'            => $guestCustomer->getEmail(),
            'acceptsMarketing' => Contants::CONTACT_STATUS_ACTIVE
        ];
    }
}
