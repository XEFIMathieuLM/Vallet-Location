<?php

namespace Functional\Inspection\Exceptions;

use Functional\Fleet\Exceptions\RefusalException;
use Functional\Fleet\Models\MachineCategory;

final class DuplicateCategoryViewLabelException extends RefusalException
{
    public static function for(MachineCategory $category, string $label): self
    {
        return new self(
            "Category {$category->id} already has a view with this label.",
            'inspection::views.refusals.duplicate_label',
            ['label' => $label],
        );
    }
}
