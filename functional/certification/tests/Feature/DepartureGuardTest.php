<?php

namespace Functional\Certification\Tests\Feature;

use Carbon\CarbonImmutable;
use Functional\Booking\Actions\DepartReservation;
use Functional\Booking\Enums\ReservationStatus;
use Functional\Booking\Models\Customer;
use Functional\Booking\Models\Reservation;
use Functional\Booking\Tests\Doubles\GuardRefusalException;
use Functional\Booking\Tests\Doubles\RefusingGuard;
use Functional\Certification\Enums\CertificateStatus;
use Functional\Certification\Enums\DispatchFailureReason;
use Functional\Certification\Exceptions\CertificateNotDeliveredException;
use Functional\Certification\Guards\CertificateDeliveredGuard;
use Functional\Certification\Models\ReservationCertificate;
use Functional\Certification\Tests\Concerns\BuildsCertificateScenarios;
use Functional\Fleet\Models\Machine;
use Functional\Fleet\Tests\Concerns\AssertsRefusals;
use Functional\Fleet\Tests\Concerns\CreatesUsers;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class DepartureGuardTest extends TestCase
{
    use AssertsRefusals, BuildsCertificateScenarios, CreatesUsers, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->setUpCertificationScenario();
        $this->keepOnlyTheCertificateGuard();
        Notification::fake();
    }

    public function test_a_customer_without_email_gets_a_confirmed_reservation_awaiting_the_email(): void
    {
        $reservation = $this->reserve($this->machineWithReport(), Customer::factory()->create(['email' => null]));

        $this->assertSame(ReservationStatus::Confirmed, $reservation->status);
        $this->assertSame(CertificateStatus::AwaitingEmail, $this->certificateOf($reservation)->status);
    }

    /**
     * @return iterable<string, array{CertificateStatus, string}>
     */
    public static function undeliveredStatuses(): iterable
    {
        yield 'e-mail manquant' => [CertificateStatus::AwaitingEmail, 'e-mail du client manquant'];
        yield 'rapport non déposé' => [CertificateStatus::AwaitingReport, 'rapport de VGP non déposé'];
        yield 'envoi en attente' => [CertificateStatus::Pending, 'envoi en attente'];
        yield 'envoi en échec' => [CertificateStatus::Failed, 'Adresse e-mail invalide'];
    }

    #[DataProvider('undeliveredStatuses')]
    public function test_the_departure_is_refused_while_the_certificate_is_not_delivered(CertificateStatus $status, string $expectedText): void
    {
        $reservation = $this->reservationStartingToday($this->machineWithReport(), Customer::factory()->create(['email' => null]));
        $this->certificateOf($reservation)->update(['status' => $status, 'last_failure_reason' => $status === CertificateStatus::Failed ? DispatchFailureReason::InvalidAddress : null]);

        $this->assertRefused(CertificateNotDeliveredException::class, $expectedText, fn () => app(DepartReservation::class)->handle($reservation));

        $this->assertSame(ReservationStatus::Confirmed, $reservation->fresh()?->status);
    }

    public function test_a_missing_certificate_refuses_the_departure_without_being_created(): void
    {
        $reservation = Reservation::factory()->for($this->machineWithReport())->between(CarbonImmutable::today(), CarbonImmutable::today()->addDays(3))->create();

        $this->assertRefused(CertificateNotDeliveredException::class, 'envoi en attente', fn () => app(DepartReservation::class)->handle($reservation));

        $this->assertSame(0, ReservationCertificate::query()->count());
    }

    public function test_a_delivered_certificate_lets_the_departure_through_and_other_guards_still_apply(): void
    {
        $reservation = $this->reservationStartingToday($this->machineWithReport(), $this->customerWithEmail());
        $this->assertSame(CertificateStatus::Sent, $this->certificateOf($reservation)->status);

        $this->keepOnlyTheCertificateGuard(RefusingGuard::class);
        $this->assertRefused(GuardRefusalException::class, 'Photos de départ manquantes.', fn () => app(DepartReservation::class)->handle($reservation));

        $this->keepOnlyTheCertificateGuard();
        app(DepartReservation::class)->handle($reservation);
        $this->assertSame(ReservationStatus::InProgress, $reservation->fresh()?->status);
    }

    public function test_a_machine_not_subject_to_vgp_departs_without_certificate(): void
    {
        $reservation = $this->reservationStartingToday(Machine::factory()->create(['is_subject_to_vgp' => false]), Customer::factory()->create(['email' => null]));

        app(DepartReservation::class)->handle($reservation);

        $this->assertSame(ReservationStatus::InProgress, $reservation->fresh()?->status);
    }

    public function test_before_the_go_live_date_the_departure_is_not_blocked(): void
    {
        config(['certification.go_live_date' => CarbonImmutable::today()->addDay()->toDateString()]);
        $reservation = $this->reservationStartingToday($this->machineWithReport(), Customer::factory()->create(['email' => null]));

        app(DepartReservation::class)->handle($reservation);

        $this->assertSame(ReservationStatus::InProgress, $reservation->fresh()?->status);
    }

    public function test_the_return_is_never_blocked_by_the_certificate(): void
    {
        $reservation = $this->reservationStartingToday($this->machineWithReport(), Customer::factory()->create(['email' => null]));

        app(CertificateDeliveredGuard::class)->beforeReturn($reservation);

        $this->assertSame(CertificateStatus::AwaitingEmail, $this->certificateOf($reservation)->status);
    }
}
