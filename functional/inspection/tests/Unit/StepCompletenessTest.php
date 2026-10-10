<?php

namespace Functional\Inspection\Tests\Unit;

use Functional\Inspection\Enums\InspectionStep;
use Functional\Inspection\Support\StepCompleteness;
use PHPUnit\Framework\TestCase;

class StepCompletenessTest extends TestCase
{
    public function test_a_step_is_complete_when_every_view_has_a_photo(): void
    {
        $completeness = new StepCompleteness(5, ['departure' => 0, 'return' => 5]);

        $this->assertTrue($completeness->isCompleteFor(InspectionStep::Departure));
        $this->assertFalse($completeness->isCompleteFor(InspectionStep::Return));
    }

    public function test_a_reservation_without_frozen_views_is_never_complete_and_has_no_photos(): void
    {
        $completeness = new StepCompleteness(0, ['departure' => 0, 'return' => 0]);

        $this->assertFalse($completeness->isCompleteFor(InspectionStep::Departure));
        $this->assertFalse($completeness->hasPhotosFor(InspectionStep::Departure));
    }

    public function test_a_step_has_photos_as_soon_as_one_view_has_one(): void
    {
        $completeness = new StepCompleteness(5, ['departure' => 0, 'return' => 4]);

        $this->assertTrue($completeness->hasPhotosFor(InspectionStep::Return));
        $this->assertSame(4, $completeness->missingCount(InspectionStep::Return));
    }

    public function test_an_unknown_step_count_means_every_view_is_missing(): void
    {
        $completeness = new StepCompleteness(3, []);

        $this->assertSame(3, $completeness->missingCount(InspectionStep::Departure));
        $this->assertFalse($completeness->hasPhotosFor(InspectionStep::Departure));
    }
}
