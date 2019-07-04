<?php
declare(strict_types=1);
/**
 */

namespace CommerceLeague\ActiveCampaign\Gateway\Request;

use CommerceLeague\ActiveCampaign\Helper\Config as ConfigHelper;
use Magento\Customer\Model\Customer as MagentoCustomer;

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
     * @param MagentoCustomer $magentoCustomer
     * @return array
     */
    public function build(MagentoCustomer $magentoCustomer): array
    {
        return [
            'connectionid' => (int)$this->configHelper->getConnectionId(),
            'externalid' => (int)$magentoCustomer->getId(),
            'email' => $magentoCustomer->getData('email'),
            'acceptsMarketing' => 1 // TODO::check how this value could be set
        ];
    }
}
