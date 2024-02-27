<?php

namespace CommerceLeague\ActiveCampaign\Model\System\Config\Source;

use CommerceLeague\ActiveCampaign\Model\System\Config\Source\Order\AllStatuses;

class Status
{

    /**
     * @var AllStatuses
     */
    protected $allStatuses;

    /**
     * @var InvoiceRepositoryInterface
     */
    protected $invoiceRepository;

    /**
     * @var CreditmemoRepositoryInterface
     */
    protected $creditmemoRepository;

    /**
     * Status constructor.
     *
     * @param AllStatuses $allStatuses
     */
    public function __construct(
        AllStatuses $allStatuses
    ) {
        $this->allStatuses          = $allStatuses;
    }

    public function toOptionArray($entity)
    {
        $statuses = [];

        $statuses = $this->allStatuses->toOptionArray();
        array_shift($statuses);

        return $statuses;
    }

    // Function to just put all status "codes" into an array.
    public function toArray($entity)
    {
        $statuses    = $this->toOptionArray($entity);
        $statusArray = [];
        foreach ($statuses as $status) {
            $statusArray[$status['value']];
        }
        return $statusArray;
    }
}
