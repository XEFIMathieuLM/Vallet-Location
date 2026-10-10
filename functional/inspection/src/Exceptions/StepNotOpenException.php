<?php

namespace Functional\Inspection\Exceptions;

use Functional\Fleet\Exceptions\RefusalException;
use Functional\Inspection\Enums\InspectionStep;

final class StepNotOpenException extends RefusalException
{
    public static function for(InspectionStep $step): self
    {
        return new self(__('inspection::photos.refusals.step_not_open', ['step' => $step->label()]));
    }
}
