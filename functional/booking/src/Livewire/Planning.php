<?php

namespace Functional\Booking\Livewire;

use Carbon\CarbonImmutable;
use Functional\Booking\Planning\PlanningCellKind;
use Functional\Booking\Planning\PlanningGrid;
use Functional\Fleet\Models\Agency;
use Functional\Fleet\Models\MachineCategory;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Validator;
use Livewire\Attributes\On;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

class Planning extends Component
{
    use WithPagination;

    private const DEFAULT_DAY_COUNT = 14;

    private const MAX_DAY_COUNT = 31;

    private const MACHINES_PER_PAGE = 25;

    #[Url(as: 'categorie')]
    public ?int $categoryId = null;

    #[Url(as: 'agence')]
    public ?int $agencyId = null;

    #[Url(as: 'du')]
    public string $startDate = '';

    #[Url(as: 'au')]
    public string $endDate = '';

    public function mount(): void
    {
        $this->startDate = $this->startDate !== '' ? $this->startDate : CarbonImmutable::today()->toDateString();
        $this->endDate = $this->endDate !== '' ? $this->endDate : CarbonImmutable::parse($this->startDate)->addDays(self::DEFAULT_DAY_COUNT - 1)->toDateString();
    }

    public function clearFilters(): void
    {
        $this->reset('categoryId', 'agencyId');
    }

    public function updated(): void
    {
        $this->resetPage();
    }

    #[On('echo-private:fleet,.reservation.changed')]
    #[On('echo-private:fleet,.machine.changed')]
    #[On('echo-private:fleet,.fleet.imported')]
    public function refreshOnFleetChange(): void {}

    public function render(): View
    {
        $isValidPeriod = Validator::make(
            ['start_date' => $this->startDate, 'end_date' => $this->endDate],
            ['start_date' => ['required', 'date_format:Y-m-d'], 'end_date' => ['required', 'date_format:Y-m-d', 'after_or_equal:start_date']],
        )->passes();
        $machines = PlanningGrid::machinesQuery(
            MachineCategory::query()->find($this->categoryId),
            Agency::query()->find($this->agencyId),
        )->paginate(self::MACHINES_PER_PAGE);
        $grid = null;

        if ($isValidPeriod) {
            $startDate = CarbonImmutable::parse($this->startDate);
            $endDate = CarbonImmutable::parse($this->endDate)->min($startDate->addDays(self::MAX_DAY_COUNT - 1));
            $grid = PlanningGrid::build($machines->items(), $startDate, $endDate);
        }

        return view('booking::livewire.planning', [
            'grid' => $grid,
            'machines' => $machines,
            'cellKinds' => PlanningCellKind::cases(),
            'categories' => MachineCategory::query()->orderBy('name')->get(),
            'agencies' => Agency::query()->orderBy('name')->get(),
        ])->title(__('booking::reservations.planning.title'));
    }
}
