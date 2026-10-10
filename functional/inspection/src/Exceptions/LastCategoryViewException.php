<?php

namespace Functional\Inspection\Exceptions;

use Functional\Fleet\Exceptions\RefusalException;
use Functional\Fleet\Models\MachineCategory;

final class LastCategoryViewException extends RefusalException
{
    public static function for(MachineCategory $category): self
    {
        return new self(
            "Category {$category->id} must keep at least one view.",
            'inspection::views.refusals.last_view',
        );
    }
}
