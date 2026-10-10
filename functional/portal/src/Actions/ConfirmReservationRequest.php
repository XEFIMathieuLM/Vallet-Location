<?php

namespace Functional\Portal\Actions;

use Functional\Booking\Actions\CreateReservation;
use Functional\Booking\Data\NewCustomer;
use Functional\Booking\Models\Customer;
use Functional\Booking\Models\Reservation;
use Functional\Fleet\Contracts\AgencyMember;
use Functional\Fleet\Models\Machine;
use Functional\Portal\Data\CustomerChoice;
use Functional\Portal\Enums\CustomerChoiceKind;
use Functional\Portal\Enums\PortalHistoryEvent;
use Functional\Portal\Events\ReservationRequestChanged;
use Functional\Portal\Exceptions\ConfirmationMachineMismatchException;
use Functional\Portal\History\PortalHistory;
use Functional\Portal\Jobs\NotifyRequestDecisionJob;
use Functional\Portal\Models\CustomerAccount;
use Functional\Portal\Models\ReservationRequest;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Facades\DB;

final class ConfirmReservationRequest
{
    public function __construct(
        private readonly CreateReservation $createReservation,
        private readonly AttachCustomerAccount $attachCustomerAccount,
        private readonly PortalHistory $portalHistory,
    ) {}

    public function handle(ReservationRequest $reservationRequest, Machine $machine, CustomerChoice $customerChoice, Authenticatable&AgencyMember $author): ReservationRequest
    {
        $confirmed = DB::transaction(fn (): ReservationRequest => $this->confirm($reservationRequest, $machine, $customerChoice, $author));

        ReservationRequestChanged::dispatch($confirmed);
        NotifyRequestDecisionJob::dispatch($confirmed->id)->afterCommit();

        return $confirmed;
    }

    private function confirm(ReservationRequest $reservationRequest, Machine $machine, CustomerChoice $customerChoice, Authenticatable&AgencyMember $author): ReservationRequest
    {
        $locked = ReservationRequest::query()->with('machine')->whereKey($reservationRequest->id)->lockForUpdate()->firstOrFail();
        $nextState = $locked->state()->confirm();
        $account = CustomerAccount::query()->whereKey($locked->customer_account_id)->lockForUpdate()->firstOrFail();

        if ($machine->machine_category_id !== $locked->machine->machine_category_id) {
            throw ConfirmationMachineMismatchException::for($machine, $locked->machine);
        }

        $reservation = $this->createReservation->handle($author, $machine, $this->customerFor($account, $customerChoice), $locked->start_date, $locked->end_date);
        $isNewAttachment = ! $account->isAttached();

        if ($isNewAttachment) {
            $this->attachCustomerAccount->handle($account, $reservation->customer, $author);
        }

        $locked->update([
            'status' => $nextState->status(),
            'reservation_id' => $reservation->id,
            'decided_by' => $author->getKey(),
            'decided_agency_id' => $author->agencyId(),
            'decided_at' => now(),
        ]);
        $this->recordHistory($locked, $reservation, $isNewAttachment, $customerChoice, $author);

        return $locked;
    }

    private function customerFor(CustomerAccount $account, CustomerChoice $customerChoice): Customer|NewCustomer
    {
        if ($account->customer_id !== null) {
            return Customer::query()->findOrFail($account->customer_id);
        }

        if ($customerChoice->kind === CustomerChoiceKind::Existing) {
            return Customer::query()->findOrFail($customerChoice->existingCustomerId);
        }

        return new NewCustomer($account->name, $account->phone, $account->email, $account->declared_type);
    }

    private function recordHistory(ReservationRequest $reservationRequest, Reservation $reservation, bool $isNewAttachment, CustomerChoice $customerChoice, Authenticatable&AgencyMember $author): void
    {
        $this->portalHistory->record($reservationRequest, PortalHistoryEvent::RequestConfirmed, $author, [
            'reservation_id' => $reservation->id,
            'machine_reference' => $reservation->machine->reference,
            'attachment' => $isNewAttachment ? $customerChoice->kind->value : null,
        ]);
    }
}
