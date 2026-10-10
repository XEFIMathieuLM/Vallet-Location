<?php

namespace Functional\Inspection\Livewire;

use Flux\Flux;
use Functional\Booking\Models\Reservation;
use Functional\Fleet\Livewire\Concerns\DisplaysRefusals;
use Functional\Inspection\Access\InspectionPermission;
use Functional\Inspection\Actions\ReportDamage;
use Functional\Inspection\Livewire\Concerns\ActsAsAgencyMember;
use Functional\Inspection\Livewire\Concerns\ResolvesDamages;
use Functional\Inspection\Models\Damage;
use Functional\Inspection\Models\ReservationView;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Locked;
use Livewire\Component;

class Comparison extends Component
{
    use ActsAsAgencyMember, DisplaysRefusals, ResolvesDamages;

    #[Locked]
    public Reservation $reservation;

    public ?int $reservationViewId = null;

    public string $comment = '';

    public function mount(Reservation $reservation): void
    {
        $this->reservation = $reservation;
    }

    public function report(): void
    {
        Gate::authorize(InspectionPermission::ManageDamages->value);

        $this->validate([
            'reservationViewId' => ['required', 'integer'],
            'comment' => ['required', 'string', 'max:2000'],
        ]);

        app(ReportDamage::class)->handle($this->reservation, (int) $this->reservationViewId, $this->comment, $this->agencyMember());

        $this->reset('reservationViewId', 'comment');

        Flux::toast(text: __('inspection::damages.report.done'), variant: 'success');
    }

    public function render(): View
    {
        $this->reservation->loadMissing('machine', 'customer');

        return view('inspection::livewire.comparison', [
            'views' => ReservationView::query()
                ->whereBelongsTo($this->reservation)
                ->with(['photos' => fn (HasMany $photos): HasMany => $photos->with(['media', 'session.author'])->oldest('id')])
                ->orderBy('position')
                ->get(),
            'damages' => Damage::query()
                ->whereBelongsTo($this->reservation)
                ->with(['view', 'reporter', 'resolver'])
                ->latest('reported_at')
                ->get(),
        ])->title(__('inspection::damages.comparison.title', ['reference' => $this->reservation->machine->reference]));
    }
}
