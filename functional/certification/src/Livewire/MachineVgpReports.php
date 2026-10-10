<?php

namespace Functional\Certification\Livewire;

use Carbon\CarbonImmutable;
use Flux\Flux;
use Functional\Certification\Actions\DepositVgpReport;
use Functional\Certification\Enums\CertificationPermission;
use Functional\Certification\Livewire\Concerns\ActsAsAgencyMember;
use Functional\Certification\Models\VgpReport;
use Functional\Fleet\Livewire\Concerns\DisplaysRefusals;
use Functional\Fleet\Models\Machine;
use Illuminate\Contracts\View\View;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Locked;
use Livewire\Component;
use Livewire\WithFileUploads;

class MachineVgpReports extends Component
{
    use ActsAsAgencyMember, DisplaysRefusals, WithFileUploads;

    #[Locked]
    public Machine $machine;

    public ?UploadedFile $reportFile = null;

    public string $verifiedOn = '';

    public string $dueOn = '';

    public function deposit(DepositVgpReport $depositVgpReport): void
    {
        Gate::authorize(CertificationPermission::Manage->value);

        $this->validate([
            'reportFile' => ['required', 'file'],
            'verifiedOn' => ['required', 'date_format:Y-m-d'],
            'dueOn' => ['required', 'date_format:Y-m-d'],
        ], attributes: [
            'reportFile' => __('certification::reports.screens.file'),
            'verifiedOn' => __('certification::reports.screens.verified_on'),
            'dueOn' => __('certification::reports.screens.due_on'),
        ]);

        $depositVgpReport->handle($this->agencyMember(), $this->machine, $this->reportFile, CarbonImmutable::parse($this->verifiedOn), CarbonImmutable::parse($this->dueOn));

        $this->reset('reportFile', 'verifiedOn', 'dueOn');
        Flux::toast(text: __('certification::reports.screens.deposited'), variant: 'success');
    }

    public function render(): View
    {
        return view('certification::livewire.machine-vgp-reports', [
            'reports' => VgpReport::query()->whereBelongsTo($this->machine)->with('depositor')->latest('id')->get(),
        ])->title(__('certification::reports.screens.machine_title', ['reference' => $this->machine->reference]));
    }
}
