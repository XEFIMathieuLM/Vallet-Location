<?php

namespace Functional\Inspection\Exceptions;

use Functional\Fleet\Exceptions\RefusalException;

final class DuplicateCategoryViewLabelException extends RefusalException
{
    public static function for(string $label): self
    {
        return new self(__('inspection::views.refusals.duplicate_label', ['label' => $label]));
    }
}
