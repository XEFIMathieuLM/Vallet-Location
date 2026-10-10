<?php

namespace Functional\Inspection\Actions\CategoryViews;

use Functional\Fleet\Models\MachineCategory;
use Illuminate\Support\Facades\DB;

class MoveCategoryView
{
    public function __construct(private readonly EditableCategoryViews $editableCategoryViews) {}

    public function handle(MachineCategory $category, int $position, int $offset): void
    {
        DB::transaction(function () use ($category, $position, $offset): void {
            $views = $this->editableCategoryViews->for($category);
            $movedView = $views->firstOrFail('position', $position);
            $swappedView = $views->firstWhere('position', $position + $offset);

            if ($swappedView === null) {
                return;
            }

            $swappedView->update(['position' => $movedView->position]);
            $movedView->update(['position' => $position + $offset]);
        });
    }
}
