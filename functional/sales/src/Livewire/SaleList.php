<?php

namespace Functional\Sales\Livewire;

use Carbon\CarbonImmutable;
use Functional\Billing\Enums\BillableLineType;
use Functional\Billing\Models\Transmission;
use Functional\Fleet\Models\Agency;
use Functional\Fleet\Models\MachineCategory;
use Functional\Sales\Enums\SaleStatus;
use Functional\Sales\Models\Sale;
use Functional\Sales\Queries\SaleListQuery;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
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

    #[Url(as: 'du')]
    public string $periodStart = '';

    #[Url(as: 'au')]
    public string $periodEnd = '';

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
        return $this->filteredSales()->paginate(self::PER_PAGE);
    }

    #[On('echo-private:sales,.sale.changed')]
    public function refreshOnSaleChange(): void {}

    public function clearFilters(): void
    {
        $this->reset('status', 'categoryId', 'agencyId', 'periodStart', 'periodEnd');
    }

    public function render(): View
    {
        return view('sales::livewire.sale-list', [
            'statuses' => SaleStatus::cases(),
            'categories' => MachineCategory::query()->orderBy('name')->get(),
            'agencies' => Agency::query()->orderBy('name')->get(),
            'concludedTotal' => app(SaleListQuery::class)->concludedTotal($this->filteredSales()),
            'transmissions' => Transmission::query()
                ->where('source_type', BillableLineType::UsedMachineSale)
                ->whereIn('source_id', array_map(fn (Sale $sale): int => $sale->id, $this->sales->items()))
                ->get()
                ->keyBy('source_id'),
            'today' => CarbonImmutable::today(),
        ])->title(__('sales::sales.list.title'));
    }

    /**
     * @return Builder<Sale>
     */
    private function filteredSales(): Builder
    {
        return app(SaleListQuery::class)->query(
            SaleStatus::tryFrom($this->status),
            MachineCategory::query()->find($this->categoryId),
            Agency::query()->find($this->agencyId),
            $this->periodDate($this->periodStart),
            $this->periodDate($this->periodEnd),
        );
    }

    private function periodDate(string $typedDate): ?CarbonImmutable
    {
        return preg_match('/^\d{4}-\d{2}-\d{2}$/', $typedDate) === 1 ? CarbonImmutable::parse($typedDate) : null;
    }
}
