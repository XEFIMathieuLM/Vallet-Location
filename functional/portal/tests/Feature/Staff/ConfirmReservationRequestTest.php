<?php

namespace Functional\Portal\Tests\Feature\Staff;

use Carbon\CarbonImmutable;
use Functional\Booking\Enums\CustomerType;
use Functional\Booking\Enums\ReservationStatus;
use Functional\Booking\Exceptions\MachineNotReservableException;
use Functional\Booking\Exceptions\ReservationOverlapException;
use Functional\Booking\Models\Customer;
use Functional\Booking\Models\Reservation;
use Functional\Fleet\Contracts\AgencyMember;
use Functional\Fleet\Enums\MachineStatus;
use Functional\Fleet\Models\Machine;
use Functional\Fleet\Models\MachineCategory;
use Functional\Fleet\Tests\Concerns\AssertsRefusals;
use Functional\Portal\Actions\ConfirmReservationRequest;
use Functional\Portal\Data\CustomerChoice;
use Functional\Portal\Enums\PortalHistoryEvent;
use Functional\Portal\Enums\ReservationRequestStatus;
use Functional\Portal\Exceptions\ConfirmationMachineMismatchException;
use Functional\Portal\Livewire\Staff\ConfirmRequestModal;
use Functional\Portal\Models\CustomerAccount;
use Functional\Portal\Models\ReservationRequest;
use Functional\Portal\Tests\Concerns\BuildsPortalFixtures;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;
use Spatie\Activitylog\Models\Activity;
use Tests\TestCase;

class ConfirmReservationRequestTest extends TestCase
{
    use AssertsRefusals, BuildsPortalFixtures, RefreshDatabase;

    private Model&Authenticatable&AgencyMember $handler;

    private Machine $machine;

    protected function setUp(): void
    {
        parent::setUp();
        $this->travelTo(CarbonImmutable::parse('2026-10-10 09:00', 'Europe/Paris'));
        Notification::fake();
        $this->seedPermissions();
        $this->handler = $this->requestHandler();
        $this->machine = $this->reservableMachine();
        $this->actingAs($this->handler);
    }

    public function test_confirming_a_request_of_an_attached_account_creates_the_reservation_for_its_customer_record(): void
    {
        $customer = Customer::factory()->create();
        $reservationRequest = $this->pendingRequest($this->attachedCustomerAccount($customer), $this->machine, '2026-11-10', '2026-11-14');

        $confirmed = $this->confirm($reservationRequest, CustomerChoice::create());

        $reservation = Reservation::query()->sole();
        $this->assertSame(ReservationRequestStatus::Confirmed, $confirmed->status);
        $this->assertSame($reservation->id, $confirmed->reservation_id);
        $this->assertSame($customer->id, $reservation->customer_id);
        $this->assertSame($this->machine->id, $reservation->machine_id);
        $this->assertSame(ReservationStatus::Confirmed, $reservation->status);
        $this->assertSame('2026-11-10', $reservation->start_date->toDateString());
        $this->assertSame('2026-11-14', $reservation->end_date->toDateString());
        $this->assertSame($this->handler->getKey(), $reservation->created_by);
        $this->assertSame($this->handler->agencyId(), $reservation->agency_id);
        $this->assertSame($this->handler->getKey(), $confirmed->decided_by);
        $this->assertSame($this->handler->agencyId(), $confirmed->decided_agency_id);
        $this->assertNotNull($confirmed->decided_at);
        $this->assertSame(1, Customer::query()->count());
        $this->assertTrue(Activity::query()->where('event', PortalHistoryEvent::RequestConfirmed->value)->whereMorphedTo('subject', $confirmed)->exists());
    }

    public function test_an_unattached_account_is_attached_to_the_existing_record_chosen_by_the_employee(): void
    {
        $account = $this->customerAccount(['email' => 'contact@martin.fr', 'phone' => '02 35 11 22 33']);
        $sameEmail = Customer::factory()->create(['email' => 'CONTACT@martin.fr']);
        Customer::factory()->create(['phone' => '02 35 11 22 33']);
        Customer::factory()->create(['email' => 'autre@exemple.fr']);
        $reservationRequest = $this->pendingRequest($account, $this->machine, '2026-11-10', '2026-11-14');

        Livewire::test(ConfirmRequestModal::class, ['reservationRequestId' => $reservationRequest->id])
            ->assertViewHas('suggestedCustomers', fn ($suggestedCustomers): bool => $suggestedCustomers->count() === 2 && $suggestedCustomers->contains($sameEmail))
            ->set('customerChoice', 'existing')
            ->set('existingCustomerId', $sameEmail->id)
            ->call('confirm')
            ->assertHasNoErrors()
            ->assertDispatched('reservation-request-decided');

        $this->assertSame($sameEmail->id, $account->fresh()?->customer_id);
        $this->assertSame($sameEmail->id, Reservation::query()->sole()->customer_id);
        $this->assertTrue(Activity::query()->where('event', PortalHistoryEvent::AccountAttached->value)->whereMorphedTo('subject', $sameEmail)->exists());
    }

    public function test_an_unattached_account_gets_a_new_record_built_from_its_declared_information(): void
    {
        $account = CustomerAccount::factory()->individual()->create(['name' => 'Julie Morel', 'email' => 'julie.morel@exemple.fr', 'phone' => '06 11 22 33 44']);
        $reservationRequest = $this->pendingRequest($account, $this->machine, '2026-11-10', '2026-11-14');

        $this->confirm($reservationRequest, CustomerChoice::create());

        $customer = Customer::query()->sole();
        $this->assertSame('Julie Morel', $customer->name);
        $this->assertSame('julie.morel@exemple.fr', $customer->email);
        $this->assertSame('06 11 22 33 44', $customer->phone);
        $this->assertSame(CustomerType::Individual, $customer->type);
        $this->assertSame($customer->id, $account->fresh()?->customer_id);
    }

    public function test_a_refusal_of_feature_001_keeps_the_request_pending_and_creates_nothing(): void
    {
        $reservationRequest = $this->pendingRequest($this->customerAccount(), $this->machine, '2026-11-10', '2026-11-14');
        Reservation::factory()->for($this->machine)->between(CarbonImmutable::parse('2026-11-13'), CarbonImmutable::parse('2026-11-16'))->create();

        $this->assertRefused(ReservationOverlapException::class, '', fn () => $this->confirm($reservationRequest, CustomerChoice::create()));

        $this->machine->update(['status' => MachineStatus::OutOfOrder]);
        $this->assertRefused(MachineNotReservableException::class, '', fn () => $this->confirm($reservationRequest, CustomerChoice::create()));

        $this->assertSame(ReservationRequestStatus::Pending, $reservationRequest->fresh()?->status);
        $this->assertSame(1, Reservation::query()->count());
        $this->assertSame(1, Customer::query()->count());
        $this->assertNull($reservationRequest->account->fresh()?->customer_id);
    }

    public function test_the_modal_shows_the_refusal_and_keeps_the_request_pending(): void
    {
        $reservationRequest = $this->pendingRequest($this->customerAccount(), $this->machine, '2026-11-10', '2026-11-14');
        $this->machine->update(['status' => MachineStatus::Workshop]);

        Livewire::test(ConfirmRequestModal::class, ['reservationRequestId' => $reservationRequest->id])
            ->set('machineId', $this->machine->id)
            ->set('customerChoice', 'create')
            ->call('confirm')
            ->assertHasErrors('refusal');

        $this->assertSame(ReservationRequestStatus::Pending, $reservationRequest->fresh()?->status);
    }

    public function test_the_request_can_be_confirmed_on_another_machine_of_the_same_category_only(): void
    {
        $reservationRequest = $this->pendingRequest($this->customerAccount(), $this->machine, '2026-11-10', '2026-11-14');
        $sameCategory = $this->reservableMachine($this->machine->category);
        $otherCategory = $this->reservableMachine(MachineCategory::factory()->create());

        Livewire::test(ConfirmRequestModal::class, ['reservationRequestId' => $reservationRequest->id])
            ->assertViewHas('machines', fn ($machines): bool => $machines->first()?->is($this->machine) && $machines->contains($sameCategory) && ! $machines->contains($otherCategory));

        $this->assertRefused(ConfirmationMachineMismatchException::class, 'même catégorie', fn () => app(ConfirmReservationRequest::class)->handle($reservationRequest, $otherCategory, CustomerChoice::create(), $this->handler));

        $this->confirm($reservationRequest, CustomerChoice::create(), $sameCategory);

        $this->assertSame($sameCategory->id, Reservation::query()->sole()->machine_id);
    }

    private function confirm(ReservationRequest $reservationRequest, CustomerChoice $customerChoice, ?Machine $machine = null): ReservationRequest
    {
        return app(ConfirmReservationRequest::class)->handle($reservationRequest, $machine ?? $this->machine, $customerChoice, $this->handler);
    }
}
