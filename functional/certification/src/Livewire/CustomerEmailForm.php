<?php

namespace Functional\Certification\Livewire;

use Flux\Flux;
use Functional\Booking\Actions\UpdateCustomer;
use Functional\Booking\Models\Reservation;
use Functional\Certification\Enums\CertificationHistoryEvent;
use Functional\Certification\Enums\CertificationPermission;
use Functional\Certification\History\CertificationHistory;
use Functional\Certification\Livewire\Concerns\ActsAsAgencyMember;
use Functional\Fleet\Livewire\Concerns\DisplaysRefusals;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Locked;
use Livewire\Component;

class CustomerEmailForm extends Component
{
    use ActsAsAgencyMember, DisplaysRefusals;

    public const NAME = 'certification.customer-email-form';

    #[Locked]
    public Reservation $reservation;

    public string $email = '';

    public function mount(Reservation $reservation): void
    {
        $this->reservation = $reservation;
        $this->email = $reservation->customer->email ?? '';
    }

    public function save(UpdateCustomer $updateCustomer, CertificationHistory $certificationHistory): void
    {
        Gate::authorize(CertificationPermission::Manage->value);

        $previousEmail = $this->reservation->customer->email;
        $customer = $updateCustomer->changeEmail($this->reservation->customer, $this->email, $this->agencyMember());

        if ($customer->email !== $previousEmail) {
            $certificationHistory->record($this->reservation, CertificationHistoryEvent::CustomerEmailUpdated, ['previous' => $previousEmail ?? '—', 'email' => $customer->email]);
        }

        $this->dispatch('certificate-updated');
        Flux::toast(text: __('certification::certificates.section.email_saved'), variant: 'success');
    }

    public function render(): View
    {
        return view('certification::livewire.customer-email-form');
    }
}
