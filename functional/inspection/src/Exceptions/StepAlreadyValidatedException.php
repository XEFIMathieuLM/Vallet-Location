<?php

namespace Functional\Inspection\Exceptions;

use Functional\Fleet\Exceptions\RefusalException;
use Functional\Inspection\Enums\InspectionStep;

final class StepAlreadyValidatedException extends RefusalException
{
    public static function for(InspectionStep $step): self
    {
        return new self(__('inspection::photos.refusals.step_validated', ['step' => $step->label()]));
    }
}
