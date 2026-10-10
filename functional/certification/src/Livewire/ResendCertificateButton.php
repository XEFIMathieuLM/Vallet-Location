<?php

namespace Functional\Certification\Livewire;

use Flux\Flux;
use Functional\Booking\Models\Reservation;
use Functional\Certification\Actions\ResendCertificate;
use Functional\Certification\Enums\CertificationPermission;
use Functional\Certification\Livewire\Concerns\ActsAsAgencyMember;
use Functional\Fleet\Livewire\Concerns\DisplaysRefusals;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Locked;
use Livewire\Component;

class ResendCertificateButton extends Component
{
    use ActsAsAgencyMember, DisplaysRefusals;

    public const NAME = 'certification.resend-certificate-button';

    #[Locked]
    public Reservation $reservation;

    public function resend(ResendCertificate $resendCertificate): void
    {
        Gate::authorize(CertificationPermission::Manage->value);

        $resendCertificate->handle($this->reservation, $this->agencyMember());

        $this->dispatch('certificate-updated');
        Flux::toast(text: __('certification::certificates.section.resent'), variant: 'success');
    }

    public function render(): View
    {
        return view('certification::livewire.resend-certificate-button');
    }
}
