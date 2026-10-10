<?php

namespace Functional\Booking\Livewire;

use Carbon\CarbonImmutable;
use Functional\Booking\Queries\AvailableMachinesQuery;
use Functional\Fleet\Models\Agency;
use Functional\Fleet\Models\Machine;
use Functional\Fleet\Models\MachineCategory;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Validator;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Url;
use Livewire\Component;

/**
 * @property-read bool $hasValidPeriod
 * @property-read Collection<int, Machine> $machines
 */
class AvailabilitySearch extends Component
{
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
        $this->endDate = $this->endDate !== '' ? $this->endDate : $this->startDate;
    }

    #[Computed]
    public function hasValidPeriod(): bool
    {
        return Validator::make(
            ['start_date' => $this->startDate, 'end_date' => $this->endDate],
            ['start_date' => ['required', 'date_format:Y-m-d'], 'end_date' => ['required', 'date_format:Y-m-d', 'after_or_equal:start_date']],
        )->passes();
    }

    /**
     * @return Collection<int, Machine>
     */
    #[Computed]
    public function machines(): Collection
    {
        if (! $this->hasValidPeriod) {
            return new Collection;
        }

        return app(AvailableMachinesQuery::class)->get(
            CarbonImmutable::parse($this->startDate),
            CarbonImmutable::parse($this->endDate),
            $this->categoryId,
            $this->agencyId,
        );
    }

    public function render(): View
    {
        return view('booking::livewire.availability-search', [
            'categories' => MachineCategory::query()->orderBy('name')->get(),
            'agencies' => Agency::query()->orderBy('name')->get(),
        ])->title(__('booking::reservations.availability.title'));
    }
}
