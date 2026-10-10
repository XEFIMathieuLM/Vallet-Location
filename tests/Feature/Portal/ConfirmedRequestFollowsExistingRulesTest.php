<?php

namespace Tests\Feature\Portal;

use Carbon\CarbonImmutable;
use Functional\Accounts\Exceptions\MissingPurchaseOrderException;
use Functional\Accounts\Guards\PurchaseOrderDepartureGuard;
use Functional\Accounts\Models\KeyAccount;
use Functional\Booking\Actions\DepartReservation;
use Functional\Booking\Contracts\ReservationTransitionGuard;
use Functional\Booking\Extensions\ReservationTransitionGuards;
use Functional\Booking\Models\Customer;
use Functional\Booking\Models\Reservation;
use Functional\Certification\Enums\CertificateStatus;
use Functional\Certification\Models\ReservationCertificate;
use Functional\Certification\Models\VgpReport;
use Functional\Certification\Notifications\VgpCertificateNotification;
use Functional\Deposit\Exceptions\DepositRefusedException;
use Functional\Deposit\Guards\DepositCollectedGuard;
use Functional\Fleet\Models\Machine;
use Functional\Inspection\Exceptions\MissingPhotosException;
use Functional\Inspection\Guards\PhotosCompleteGuard;
use Functional\Portal\Actions\ConfirmReservationRequest;
use Functional\Portal\Data\CustomerChoice;
use Functional\Portal\Enums\ReservationRequestStatus;
use Functional\Portal\Models\CustomerAccount;
use Functional\Portal\Models\ReservationRequest;
use Functional\Portal\Tests\Concerns\BuildsPortalFixtures;
use Functional\Sales\Exceptions\MachineReservedForSaleException;
use Functional\Sales\Models\Sale;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Notifications\AnonymousNotifiable;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;
use Throwable;

class ConfirmedRequestFollowsExistingRulesTest extends TestCase
{
    use BuildsPortalFixtures, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->travelTo(CarbonImmutable::parse('2026-10-10 09:00', 'Europe/Paris'));
        Notification::fake();
        Storage::fake('vgp-reports');
        config(['certification.go_live_date' => '2026-10-01']);
        $this->seedPermissions();
    }

    public function test_the_departure_needs_the_departure_photos(): void
    {
        $reservation = $this->confirmedReservation($this->customerAccount(), CustomerChoice::create());

        $this->assertDepartureRefusedBy(PhotosCompleteGuard::class, MissingPhotosException::class, $reservation);
    }

    public function test_a_new_individual_customer_must_pay_the_deposit_before_departure(): void
    {
        $reservation = $this->confirmedReservation(CustomerAccount::factory()->individual()->create(), CustomerChoice::create());

        $this->assertDepartureRefusedBy(DepositCollectedGuard::class, DepositRefusedException::class, $reservation);
    }

    public function test_a_key_account_needs_a_purchase_order_before_departure(): void
    {
        $keyAccount = KeyAccount::factory()->create();
        $account = $this->customerAccount();
        $reservation = $this->confirmedReservation($account, CustomerChoice::existing($keyAccount->customer_id));

        $this->assertDepartureRefusedBy(PurchaseOrderDepartureGuard::class, MissingPurchaseOrderException::class, $reservation);
    }

    public function test_the_vgp_certificate_is_sent_to_the_email_of_the_customer_record(): void
    {
        $machine = Machine::factory()->vgpValid()->create();
        VgpReport::factory()->for($machine)->create();
        $customer = Customer::factory()->create(['email' => 'chantier@exemple.fr']);
        $reservation = $this->confirmedReservation($this->attachedCustomerAccount($customer), CustomerChoice::create(), $machine);

        $this->assertSame(CertificateStatus::Sent, ReservationCertificate::query()->whereBelongsTo($reservation)->sole()->status);
        Notification::assertSentOnDemand(VgpCertificateNotification::class, fn (VgpCertificateNotification $notification, array $channels, AnonymousNotifiable $notifiable): bool => $notifiable->routes['mail'] === 'chantier@exemple.fr');
    }

    public function test_a_machine_reserved_for_a_sale_cannot_be_confirmed(): void
    {
        $machine = $this->reservableMachine();
        $reservationRequest = $this->pendingRequest($this->customerAccount(), $machine, '2026-11-10', '2026-11-14');
        Sale::factory()->for($machine)->reserved(CarbonImmutable::parse('2026-11-12'))->create();

        $refusal = rescue(fn () => app(ConfirmReservationRequest::class)->handle($reservationRequest, $machine, CustomerChoice::create(), $this->requestHandler()), fn (Throwable $exception): Throwable => $exception, report: false);

        $this->assertInstanceOf(MachineReservedForSaleException::class, $refusal);
        $this->assertSame(ReservationRequestStatus::Pending, $reservationRequest->fresh()?->status);
    }

    private function confirmedReservation(CustomerAccount $account, CustomerChoice $customerChoice, ?Machine $machine = null): Reservation
    {
        $machine ??= $this->reservableMachine();
        $reservationRequest = $this->pendingRequest($account, $machine, '2026-11-10', '2026-11-14');

        $confirmed = app(ConfirmReservationRequest::class)->handle($reservationRequest, $machine, $customerChoice, $this->requestHandler());

        return Reservation::query()->findOrFail($confirmed->reservation_id);
    }

    /**
     * @param  class-string<ReservationTransitionGuard>  $guardClass
     * @param  class-string<Throwable>  $refusalClass
     */
    private function assertDepartureRefusedBy(string $guardClass, string $refusalClass, Reservation $reservation): void
    {
        $onlyThisGuard = new ReservationTransitionGuards;
        $onlyThisGuard->register($guardClass);
        $this->app->instance(ReservationTransitionGuards::class, $onlyThisGuard);
        $this->travelTo(CarbonImmutable::parse('2026-11-10 08:00', 'Europe/Paris'));

        $refusal = rescue(fn () => app(DepartReservation::class)->handle($reservation), fn (Throwable $exception): Throwable => $exception, report: false);

        $this->assertInstanceOf($refusalClass, $refusal);
        $this->assertSame(ReservationRequestStatus::Confirmed, ReservationRequest::query()->where('reservation_id', $reservation->id)->sole()->status);
    }
}
