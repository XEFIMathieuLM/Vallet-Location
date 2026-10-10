<?php

namespace Functional\Inspection\Actions\CategoryViews;

use Functional\Fleet\Models\MachineCategory;
use Functional\Inspection\Actions\ResolveRequiredViews;
use Functional\Inspection\Exceptions\DuplicateCategoryViewLabelException;
use Functional\Inspection\Models\CategoryView;
use Illuminate\Database\Eloquent\Collection;

class EditableCategoryViews
{
    public function __construct(private readonly ResolveRequiredViews $resolveRequiredViews) {}

    /**
     * @return Collection<int, CategoryView>
     */
    public function for(MachineCategory $category): Collection
    {
        MachineCategory::query()->whereKey($category->id)->lockForUpdate()->firstOrFail();

        if (! CategoryView::query()->where('machine_category_id', $category->id)->exists()) {
            foreach ($this->resolveRequiredViews->defaultLabels() as $offset => $label) {
                CategoryView::query()->create([
                    'machine_category_id' => $category->id,
                    'label' => $label,
                    'position' => $offset + 1,
                ]);
            }
        }

        return CategoryView::query()
            ->where('machine_category_id', $category->id)
            ->orderBy('position')
            ->get();
    }

    /**
     * @param  Collection<int, CategoryView>  $views
     */
    public function ensureLabelIsFree(Collection $views, string $label, ?int $ignoredViewId = null): void
    {
        $isTaken = $views->contains(
            fn (CategoryView $view): bool => $view->id !== $ignoredViewId && mb_strtolower($view->label) === mb_strtolower($label),
        );

        if ($isTaken) {
            throw DuplicateCategoryViewLabelException::for($label);
        }
    }
}
