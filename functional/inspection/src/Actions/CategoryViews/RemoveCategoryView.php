<?php

namespace Functional\Inspection\Actions\CategoryViews;

use Functional\Fleet\Models\MachineCategory;
use Functional\Inspection\Exceptions\LastCategoryViewException;
use Functional\Inspection\Models\CategoryView;
use Illuminate\Support\Facades\DB;

class RemoveCategoryView
{
    public function __construct(private readonly EditableCategoryViews $editableCategoryViews) {}

    public function handle(MachineCategory $category, int $position): void
    {
        DB::transaction(function () use ($category, $position): void {
            $views = $this->editableCategoryViews->for($category);

            if (CategoryView::query()->whereBelongsTo($category, 'category')->count() === 1) {
                throw LastCategoryViewException::make();
            }

            $views->firstOrFail('position', $position)->delete();

            CategoryView::query()
                ->whereBelongsTo($category, 'category')
                ->where('position', '>', $position)
                ->decrement('position');
        });
    }
}
