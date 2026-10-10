<?php

namespace App\Livewire\Dashboard;

use App\Dashboard\DashboardSection;
use Carbon\CarbonImmutable;
use Functional\Certification\Enums\CertificationPermission;
use Functional\Fleet\Queries\VgpWatchList;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\On;
use Livewire\Attributes\Reactive;
use Livewire\Component;

class VgpWatch extends Component
{
    #[Reactive]
    public ?int $agencyId = null;

    public function mount(): void
    {
        Gate::authorize(CertificationPermission::Manage->value);
    }

    #[On('echo-private:fleet,.machine.changed')]
    public function refreshOnMachineChange(): void {}

    public function render(VgpWatchList $vgpWatchList): View
    {
        $today = CarbonImmutable::today();
        $until = $today->addDays(config()->integer('dashboard.vgp_watch_days'));

        return view('livewire.dashboard.vgp-watch', [
            'today' => $today,
            'machines' => DashboardSection::fromQuery($vgpWatchList->query($this->agencyId, $until), config()->integer('dashboard.section_limit')),
        ]);
    }
}
