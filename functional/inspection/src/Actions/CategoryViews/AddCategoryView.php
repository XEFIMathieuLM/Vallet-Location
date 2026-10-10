<?php

namespace Functional\Inspection\Actions\CategoryViews;

use Functional\Fleet\Models\MachineCategory;
use Functional\Inspection\Models\CategoryView;
use Illuminate\Support\Facades\DB;

class AddCategoryView
{
    public function __construct(private readonly EditableCategoryViews $editableCategoryViews) {}

    public function handle(MachineCategory $category, string $label): CategoryView
    {
        $label = trim($label);

        return DB::transaction(function () use ($category, $label): CategoryView {
            $views = $this->editableCategoryViews->for($category);
            $this->editableCategoryViews->ensureLabelIsFree($category, $views, $label);

            return CategoryView::query()->create([
                'machine_category_id' => $category->id,
                'label' => $label,
                'position' => (int) CategoryView::query()->whereBelongsTo($category, 'category')->max('position') + 1,
            ]);
        });
    }
}
