<?php

namespace Functional\Inspection\Livewire;

use Functional\Fleet\Exceptions\RefusalException;
use Functional\Fleet\Models\MachineCategory;
use Functional\Inspection\Actions\CategoryViews\AddCategoryView;
use Functional\Inspection\Actions\CategoryViews\MoveCategoryView;
use Functional\Inspection\Actions\CategoryViews\RemoveCategoryView;
use Functional\Inspection\Actions\CategoryViews\RenameCategoryView;
use Functional\Inspection\Actions\CategoryViews\ResetCategoryViews;
use Functional\Inspection\Actions\ResolveRequiredViews;
use Functional\Inspection\Models\CategoryView;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Locked;
use Livewire\Component;
use Throwable;

class CategoryViews extends Component
{
    #[Locked]
    public MachineCategory $category;

    public string $newLabel = '';

    public ?int $editedPosition = null;

    public string $editedLabel = '';

    public function mount(MachineCategory $category): void
    {
        $this->category = $category;
    }

    public function add(): void
    {
        $this->validate(['newLabel' => ['required', 'string', 'max:100']]);

        app(AddCategoryView::class)->handle($this->category, $this->newLabel);

        $this->reset('newLabel');
    }

    public function edit(int $position, string $label): void
    {
        $this->editedPosition = $position;
        $this->editedLabel = $label;
    }

    public function rename(): void
    {
        $this->validate(['editedLabel' => ['required', 'string', 'max:100'], 'editedPosition' => ['required', 'integer']]);

        app(RenameCategoryView::class)->handle($this->category, (int) $this->editedPosition, $this->editedLabel);

        $this->reset('editedPosition', 'editedLabel');
    }

    public function move(int $position, int $offset): void
    {
        app(MoveCategoryView::class)->handle($this->category, $position, $offset);
    }

    public function remove(int $position): void
    {
        app(RemoveCategoryView::class)->handle($this->category, $position);
    }

    public function resetToDefault(): void
    {
        app(ResetCategoryViews::class)->handle($this->category);
    }

    public function exception(Throwable $e, callable $stopPropagation): void
    {
        if ($e instanceof RefusalException) {
            $this->addError('refusal', $e->getMessage());
            $stopPropagation();
        }
    }

    public function render(): View
    {
        $isCustomized = CategoryView::query()->where('machine_category_id', $this->category->id)->exists();

        return view('inspection::livewire.category-views.edit', [
            'labels' => app(ResolveRequiredViews::class)->forCategory($this->category->id),
            'isCustomized' => $isCustomized,
        ])->title(__('inspection::views.edit.title', ['category' => $this->category->name]));
    }
}
