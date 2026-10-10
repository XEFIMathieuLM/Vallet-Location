<?php

namespace Functional\Certification\Livewire;

use Functional\Certification\Queries\ReportInForce;
use Functional\Fleet\Models\Agency;
use Functional\Fleet\Models\Machine;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

class VgpMachines extends Component
{
    use WithPagination;

    private const PER_PAGE = 50;

    #[Url(as: 'agence')]
    public ?int $agencyId = null;

    #[Url(as: 'sans-rapport')]
    public bool $isWithoutReportOnly = false;

    public function updated(): void
    {
        $this->resetPage();
    }

    public function render(ReportInForce $reportInForce): View
    {
        $machines = Machine::query()
            ->where('is_subject_to_vgp', true)
            ->when($this->agencyId !== null, fn (Builder $query): Builder => $query->where('agency_id', $this->agencyId))
            ->when($this->isWithoutReportOnly, fn (Builder $query): Builder => $query->whereNotExists(fn (QueryBuilder $reports) => $reports
                ->selectRaw('1')->from('vgp_reports')->whereColumn('vgp_reports.machine_id', 'machines.id')))
            ->with(['category', 'agency'])
            ->orderBy('reference')
            ->paginate(self::PER_PAGE);

        return view('certification::livewire.vgp-machines', [
            'machines' => $machines,
            'reportsInForce' => $reportInForce->forMachines($machines->pluck('id')),
            'agencies' => Agency::query()->orderBy('name')->get(),
        ])->title(__('certification::reports.screens.machines_title'));
    }
}
