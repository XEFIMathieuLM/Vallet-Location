<?php

namespace Functional\Certification\Livewire;

use Flux\Flux;
use Functional\Booking\Models\Reservation;
use Functional\Certification\Actions\RecordHandDelivery;
use Functional\Certification\Enums\CertificationPermission;
use Functional\Certification\Livewire\Concerns\ActsAsAgencyMember;
use Functional\Fleet\Livewire\Concerns\DisplaysRefusals;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Locked;
use Livewire\Component;

class HandDeliveryButton extends Component
{
    use ActsAsAgencyMember, DisplaysRefusals;

    public const NAME = 'certification.hand-delivery-button';

    #[Locked]
    public Reservation $reservation;

    public function record(RecordHandDelivery $recordHandDelivery): void
    {
        Gate::authorize(CertificationPermission::Manage->value);

        $recordHandDelivery->handle($this->reservation, $this->agencyMember());

        Flux::modal('hand-delivery-'.$this->reservation->id)->close();
        $this->dispatch('certificate-updated');
        Flux::toast(text: __('certification::certificates.section.hand_delivered'), variant: 'success');
    }

    public function render(): View
    {
        return view('certification::livewire.hand-delivery-button');
    }
}
