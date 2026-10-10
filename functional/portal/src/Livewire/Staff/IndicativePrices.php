<?php

namespace Functional\Portal\Livewire\Staff;

use Flux\Flux;
use Functional\Certification\Livewire\Concerns\ActsAsAgencyMember;
use Functional\Fleet\Livewire\Concerns\DisplaysRefusals;
use Functional\Fleet\Models\MachineCategory;
use Functional\Portal\Access\PortalPermission;
use Functional\Portal\Actions\RemoveIndicativePrice;
use Functional\Portal\Actions\SetIndicativePrice;
use Functional\Portal\Models\CategoryIndicativePrice;
use Functional\Portal\Pricing\IndicativePriceFormatter;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Gate;
use Livewire\Component;

class IndicativePrices extends Component
{
    use ActsAsAgencyMember, DisplaysRefusals;

    /**
     * @var array<int|string, string>
     */
    public array $amounts = [];

    public function mount(): void
    {
        Gate::authorize(PortalPermission::ManagePrices->value);
    }

    public function save(int $categoryId, SetIndicativePrice $setIndicativePrice): void
    {
        Gate::authorize(PortalPermission::ManagePrices->value);

        $setIndicativePrice->handle(MachineCategory::query()->findOrFail($categoryId), $this->amounts[$categoryId] ?? '', $this->agencyMember());
        unset($this->amounts[$categoryId]);
        Flux::toast(text: __('portal::prices.saved'), variant: 'success');
    }

    public function remove(int $categoryId, RemoveIndicativePrice $removeIndicativePrice): void
    {
        Gate::authorize(PortalPermission::ManagePrices->value);

        $removeIndicativePrice->handle(MachineCategory::query()->findOrFail($categoryId), $this->agencyMember());
        Flux::toast(text: __('portal::prices.removed'), variant: 'success');
    }

    public function render(IndicativePriceFormatter $priceFormatter): View
    {
        return view('portal::livewire.staff.indicative-prices', [
            'categories' => MachineCategory::query()->orderBy('name')->get(),
            'pricesByCategory' => CategoryIndicativePrice::query()->with('author')->get()->keyBy('machine_category_id'),
            'priceFormatter' => $priceFormatter,
        ])->title(__('portal::prices.title'));
    }
}
