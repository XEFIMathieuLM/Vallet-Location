<?php

namespace Functional\Inspection\Actions\CategoryViews;

use Functional\Fleet\Models\MachineCategory;
use Illuminate\Support\Facades\DB;

class RenameCategoryView
{
    public function __construct(private readonly EditableCategoryViews $editableCategoryViews) {}

    public function handle(MachineCategory $category, int $position, string $label): void
    {
        $label = trim($label);

        DB::transaction(function () use ($category, $position, $label): void {
            $views = $this->editableCategoryViews->for($category);
            $renamedView = $views->firstOrFail('position', $position);
            $this->editableCategoryViews->ensureLabelIsFree($views, $label, $renamedView->id);

            $renamedView->update(['label' => $label]);
        });
    }
}
