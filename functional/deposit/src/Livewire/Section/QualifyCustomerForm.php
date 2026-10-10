<?php

namespace Functional\Deposit\Livewire\Section;

use Flux\Flux;
use Functional\Booking\Actions\UpdateCustomer;
use Functional\Booking\Enums\CustomerType;
use Functional\Booking\Models\Reservation;
use Functional\Deposit\Access\DepositPermission;
use Functional\Fleet\Contracts\AgencyMember;
use Functional\Fleet\Livewire\Concerns\DisplaysRefusals;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Locked;
use Livewire\Component;
use Symfony\Component\HttpFoundation\Response;

class QualifyCustomerForm extends Component
{
    use DisplaysRefusals;

    public const NAME = 'deposit.qualify-customer-form';

    private const MODAL = 'qualify-customer';

    #[Locked]
    public Reservation $reservation;

    public function qualify(string $type, UpdateCustomer $updateCustomer): void
    {
        Gate::authorize(DepositPermission::ManageDeposits->value);

        $author = Auth::user();
        abort_unless($author instanceof AgencyMember, Response::HTTP_FORBIDDEN);

        $updateCustomer->qualify($this->reservation->customer, CustomerType::from($type), $author);

        Flux::modal(self::MODAL)->close();
        Flux::toast(text: __('deposit::section.qualified_toast'), variant: 'success');
        $this->dispatch('deposit-updated');
    }

    public function render(): View
    {
        return view('deposit::livewire.section.qualify-customer-form', [
            'currentType' => $this->reservation->customer->type,
            'customerTypes' => CustomerType::cases(),
        ]);
    }
}
