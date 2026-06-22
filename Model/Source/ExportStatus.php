<?php

declare(strict_types=1);

namespace CommerceLeague\ActiveCampaign\Model\Source;

use CommerceLeague\ActiveCampaign\Api\Data\FailureTrackableInterface;
use Magento\Framework\Data\OptionSourceInterface;
use Magento\Framework\Phrase;

/**
 * Options source for the export_status failure-tracking column, used by the
 * admin grid select filter (pending / synced / failed).
 */
class ExportStatus implements OptionSourceInterface
{
    /**
     * @return array<int, array{value: int, label: Phrase}>
     */
    public function toOptionArray(): array
    {
        return [
            ['value' => FailureTrackableInterface::EXPORT_STATUS_PENDING, 'label' => __('Pending')],
            ['value' => FailureTrackableInterface::EXPORT_STATUS_SYNCED, 'label' => __('Synced')],
            ['value' => FailureTrackableInterface::EXPORT_STATUS_FAILED, 'label' => __('Failed')],
        ];
    }
}
