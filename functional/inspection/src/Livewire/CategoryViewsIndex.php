<?php

namespace Functional\Inspection\Livewire;

use Functional\Fleet\Models\MachineCategory;
use Functional\Inspection\Models\CategoryView;
use Illuminate\Contracts\View\View;
use Livewire\Component;

class CategoryViewsIndex extends Component
{
    public function render(): View
    {
        $customViewCounts = CategoryView::query()
            ->selectRaw('machine_category_id, count(*) as views_count')
            ->groupBy('machine_category_id')
            ->pluck('views_count', 'machine_category_id');

        return view('inspection::livewire.category-views.index', [
            'categories' => MachineCategory::query()->orderBy('name')->get(),
            'customViewCounts' => $customViewCounts,
            'defaultViewCount' => count(config()->array('inspection.default_views')),
        ])->title(__('inspection::views.index.title'));
    }
}
