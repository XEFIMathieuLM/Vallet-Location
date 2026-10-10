<?php

namespace Functional\Sales\Livewire;

use Functional\Fleet\Models\Agency;
use Functional\Fleet\Models\MachineCategory;
use Functional\Sales\Enums\SaleStatus;
use Functional\Sales\Models\Sale;
use Functional\Sales\Queries\SaleListQuery;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * @property-read LengthAwarePaginator<int, Sale> $sales
 */
class SaleList extends Component
{
    use WithPagination;

    private const PER_PAGE = 50;

    #[Url(as: 'statut')]
    public string $status = '';

    #[Url(as: 'categorie')]
    public ?int $categoryId = null;

    #[Url(as: 'agence')]
    public ?int $agencyId = null;

    public function updated(): void
    {
        $this->resetPage();
    }

    /**
     * @return LengthAwarePaginator<int, Sale>
     */
    #[Computed]
    public function sales(): LengthAwarePaginator
    {
        return app(SaleListQuery::class)->query(
            SaleStatus::tryFrom($this->status),
            MachineCategory::query()->find($this->categoryId),
            Agency::query()->find($this->agencyId),
        )->paginate(self::PER_PAGE);
    }

    #[On('echo-private:sales,.sale.changed')]
    public function refreshOnSaleChange(): void {}

    public function clearFilters(): void
    {
        $this->reset('status', 'categoryId', 'agencyId');
    }

    public function render(): View
    {
        return view('sales::livewire.sale-list', [
            'statuses' => SaleStatus::cases(),
            'categories' => MachineCategory::query()->orderBy('name')->get(),
            'agencies' => Agency::query()->orderBy('name')->get(),
        ])->title(__('sales::sales.list.title'));
    }
}
