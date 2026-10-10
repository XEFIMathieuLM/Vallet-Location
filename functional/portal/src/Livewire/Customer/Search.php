<?php

namespace Functional\Portal\Livewire\Customer;

use Carbon\CarbonImmutable;
use Functional\Fleet\Models\Agency;
use Functional\Fleet\Models\MachineCategory;
use Functional\Portal\Pricing\IndicativePriceFormatter;
use Functional\Portal\Queries\PortalMachineSearch;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Validator;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;

#[Layout('portal::layouts.portal')]
class Search extends Component
{
    #[Url(as: 'categorie')]
    public ?int $categoryId = null;

    #[Url(as: 'agence')]
    public ?int $agencyId = null;

    #[Url(as: 'du')]
    public string $startDate = '';

    #[Url(as: 'au')]
    public string $endDate = '';

    public ?int $requestedMachineId = null;

    public function requestMachine(int $machineId): void
    {
        $this->requestedMachineId = $machineId;
        $this->modal('send-request')->show();
    }

    public function render(PortalMachineSearch $portalMachineSearch, IndicativePriceFormatter $priceFormatter): View
    {
        $category = MachineCategory::query()->find($this->categoryId);
        $agency = Agency::query()->find($this->agencyId);
        $hasCriteria = $category !== null && $agency !== null && $this->hasValidPeriod();

        return view('portal::livewire.customer.search', [
            'categories' => MachineCategory::query()->orderBy('name')->get(),
            'agencies' => Agency::query()->orderBy('name')->get(),
            'hasCriteria' => $hasCriteria,
            'offers' => $hasCriteria ? $portalMachineSearch->offers(CarbonImmutable::parse($this->startDate), CarbonImmutable::parse($this->endDate), $category, $agency) : [],
            'priceFormatter' => $priceFormatter,
        ])->title(__('portal::search.title'));
    }

    private function hasValidPeriod(): bool
    {
        return Validator::make(
            ['start_date' => $this->startDate, 'end_date' => $this->endDate],
            ['start_date' => ['required', 'date_format:Y-m-d', 'after_or_equal:today'], 'end_date' => ['required', 'date_format:Y-m-d', 'after_or_equal:start_date']],
        )->passes();
    }
}
