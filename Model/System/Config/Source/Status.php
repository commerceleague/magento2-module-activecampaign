<?php

namespace CommerceLeague\ActiveCampaign\Model\System\Config\Source;

use CommerceLeague\ActiveCampaign\Model\System\Config\Source\Order\AllStatuses;

class Status
{

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
     */
    public function __construct(protected \CommerceLeague\ActiveCampaign\Model\System\Config\Source\Order\AllStatuses $allStatuses)
    {
    }

    public function toOptionArray($entity)
    {
        $statuses = [];

        $statuses = $this->allStatuses->toOptionArray();
        array_shift($statuses);

        return $statuses;
    }

    // Function to just put all status "codes" into an array.
    public function toArray($entity): array
    {
        $statuses    = $this->toOptionArray($entity);
        $statusArray = [];
        foreach ($statuses as $status) {
            $statusArray[$status['value']];
        }
        return $statusArray;
    }
}
