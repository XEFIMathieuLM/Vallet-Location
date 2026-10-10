<?php

namespace Functional\Inspection\Actions\CategoryViews;

use Functional\Fleet\Models\MachineCategory;
use Functional\Inspection\Models\CategoryView;

class ResetCategoryViews
{
    public function handle(MachineCategory $category): void
    {
        CategoryView::query()->where('machine_category_id', $category->id)->delete();
    }
}
