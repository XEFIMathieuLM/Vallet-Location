<?php

namespace Functional\Booking\Livewire;

use Functional\Booking\Enums\ReservationStatus;
use Functional\Booking\Models\Reservation;
use Functional\Fleet\Models\Agency;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * @property-read LengthAwarePaginator<int, Reservation> $reservations
 */
class ReservationList extends Component
{
    use WithPagination;

    private const PER_PAGE = 25;

    #[Url(as: 'statut')]
    public string $status = '';

    #[Url(as: 'agence')]
    public ?int $agencyId = null;

    #[Url(as: 'du')]
    public string $startDate = '';

    #[Url(as: 'au')]
    public string $endDate = '';

    #[Url(as: 'en-conflit')]
    public bool $isInConflict = false;

    public function updated(): void
    {
        $this->resetPage();
    }

    /**
     * @return LengthAwarePaginator<int, Reservation>
     */
    #[Computed]
    public function reservations(): LengthAwarePaginator
    {
        return Reservation::query()
            ->with(['machine', 'customer', 'agency'])
            ->when(ReservationStatus::tryFrom($this->status), fn ($query, ReservationStatus $status) => $query->where('status', $status))
            ->when($this->agencyId !== null, fn ($query) => $query->where('agency_id', $this->agencyId))
            ->when($this->startDate !== '', fn ($query) => $query->whereDate('end_date', '>=', $this->startDate))
            ->when($this->endDate !== '', fn ($query) => $query->whereDate('start_date', '<=', $this->endDate))
            ->when($this->isInConflict, fn ($query) => $query->whereNotNull('conflict_reason'))
            ->orderBy('start_date')
            ->orderBy('id')
            ->paginate(self::PER_PAGE);
    }

    public function render(): View
    {
        return view('booking::livewire.reservation-list', [
            'statuses' => ReservationStatus::cases(),
            'agencies' => Agency::query()->orderBy('name')->get(),
        ])->title(__('booking::reservations.list.title'));
    }
}
