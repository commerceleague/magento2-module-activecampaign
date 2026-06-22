<?php

namespace CommerceLeague\ActiveCampaign\Model\System\Config\Source;

class Status
{

    /**
     * Status constructor.
     */
    public function __construct(protected \CommerceLeague\ActiveCampaign\Model\System\Config\Source\Order\AllStatuses $allStatuses)
    {
    }

    public function toOptionArray()
    {
        $statuses = [];

        $statuses = $this->allStatuses->toOptionArray();
        array_shift($statuses);

        return $statuses;
    }

    // Function to just put all status "codes" into an array.
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
