<?php

declare(strict_types=1);

namespace CommerceLeague\ActiveCampaign\Test\Unit\Model\Source;

use CommerceLeague\ActiveCampaign\Api\Data\FailureTrackableInterface;
use CommerceLeague\ActiveCampaign\Model\Source\ExportStatus;
use PHPUnit\Framework\TestCase;

class ExportStatusTest extends TestCase
{
    public function testToOptionArrayReturnsThePendingSyncedFailedStatuses(): void
    {
        $options = (new ExportStatus())->toOptionArray();

        $this->assertCount(3, $options);

        $values = array_column($options, 'value');
        $this->assertSame(
            [
                FailureTrackableInterface::EXPORT_STATUS_PENDING,
                FailureTrackableInterface::EXPORT_STATUS_SYNCED,
                FailureTrackableInterface::EXPORT_STATUS_FAILED,
            ],
            $values
        );

        $labels = array_map(static fn (array $option): string => (string)$option['label'], $options);
        $this->assertSame(['Pending', 'Synced', 'Failed'], $labels);
    }
}
