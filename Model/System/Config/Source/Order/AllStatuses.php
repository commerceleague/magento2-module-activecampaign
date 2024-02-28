<?php

namespace CommerceLeague\ActiveCampaign\Model\System\Config\Source\Order;

use Magento\Sales\Model\Config\Source\Order\Status;

/**
 * Class AllStatuses
 *
 * Order Statuses source model
 *
 * @package CommerceLeague\ActiveCampaign\Model\System\Config\Source\Order
 */
class AllStatuses extends Status
{

    protected $_stateStatuses = false; // Get all statuses, see \Magento\Sales\Model\Config\Source\Order\Status

    /**
     * Function to just put all order status "codes" into an array.
     *
     * @return array
     */
    public function toArray(): array
    {
        $statuses    = $this->toOptionArray();
        $statusArray = [];
        foreach ($statuses as $status) {
            $statusArray[] = $status['value'];
        }
        return $statusArray;
    }
}
