<?php

namespace Functional\Inspection\Support;

use Functional\Inspection\Enums\InspectionStep;

final class StepCompleteness
{
    /**
     * @param  array<string, int>  $missingCountsBySteps
     */
    public function __construct(
        public readonly int $viewsCount,
        private readonly array $missingCountsBySteps,
    ) {}

    public function missingCount(InspectionStep $step): int
    {
        return $this->missingCountsBySteps[$step->value] ?? $this->viewsCount;
    }

    public function hasPhotosFor(InspectionStep $step): bool
    {
        return $this->missingCount($step) < $this->viewsCount;
    }

    public function isCompleteFor(InspectionStep $step): bool
    {
        return $this->viewsCount > 0 && $this->missingCount($step) === 0;
    }
}
