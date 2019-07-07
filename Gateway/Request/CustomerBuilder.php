<?php
declare(strict_types=1);
/**
 */

namespace CommerceLeague\ActiveCampaign\Gateway\Request;

use CommerceLeague\ActiveCampaign\Helper\Config as ConfigHelper;
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
        return [
            'connectionid' => $this->configHelper->getConnectionId(),
            'externalid' => $magentoCustomer->getId(),
            'email' => $magentoCustomer->getEmail(),
            'acceptsMarketing' => 1
        ];
    }
}
