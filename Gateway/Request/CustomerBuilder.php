<?php
declare(strict_types=1);
/**
 */

namespace CommerceLeague\ActiveCampaign\Gateway\Request;

use CommerceLeague\ActiveCampaign\Helper\Config as ConfigHelper;
use CommerceLeague\ActiveCampaign\Helper\Contants;
use Magento\Customer\Api\Data\CustomerInterface as MagentoCustomerInterface;

/**
 * Class CustomerBuilder
 */
class CustomerBuilder
{
    /**
     * @var ConfigHelper
     */
    private $configHelper;

    /**
     * @param ConfigHelper $configHelper
     */
    public function __construct(ConfigHelper $configHelper)
    {
        $this->configHelper = $configHelper;
    }

    /**
     * @param MagentoCustomerInterface $magentoCustomer
     * @return array
     */
    public function build(MagentoCustomerInterface $magentoCustomer): array
    {
        $isSubscribed = $magentoCustomer->getExtensionAttributes()->getIsSubscribed();

        return [
            'connectionid' => $this->configHelper->getConnectionId(),
            'externalid' => $magentoCustomer->getId(),
            'email' => $magentoCustomer->getEmail(),
            'acceptsMarketing' => $isSubscribed ? 1 : 0
        ];
    }
}
