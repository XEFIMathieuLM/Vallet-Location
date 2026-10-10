<?php

namespace Functional\Certification\Tests\Feature;

use Carbon\CarbonImmutable;
use Functional\Booking\Enums\ReservationStatus;
use Functional\Booking\Models\Reservation;
use Functional\Certification\Enums\CertificateStatus;
use Functional\Certification\Exceptions\MissingGoLiveDateException;
use Functional\Certification\Models\ReservationCertificate;
use Functional\Certification\Notifications\VgpCertificateNotification;
use Functional\Certification\Tests\Concerns\BuildsCertificateScenarios;
use Functional\Fleet\Tests\Concerns\CreatesUsers;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class GoLiveCatchUpTest extends TestCase
{
    use BuildsCertificateScenarios, CreatesUsers, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->setUpCertificationScenario();
        Notification::fake();
        $this->travelTo(CarbonImmutable::parse('2026-11-01 10:00:00'));
        config(['certification.go_live_date' => '2026-11-02']);
    }

    public function test_the_reservations_confirmed_before_go_live_get_their_certificate_on_the_go_live_day(): void
    {
        $machine = $this->machineWithReport();
        $confirmed = $this->reserve($machine, $this->customerWithEmail());
        $secondConfirmed = $this->reserve($machine, $this->customerWithEmail('autre@exemple.fr'), startInDays: 12);
        Reservation::factory()->for($this->machineWithReport())->ongoing()->create();
        Reservation::factory()->for($this->machineWithReport())->closed()->create();

        $this->artisan('certification:reconcile')->assertSuccessful();
        $this->assertSame(0, ReservationCertificate::query()->count());

        $this->travelTo(CarbonImmutable::parse('2026-11-02 00:05:00', 'Europe/Paris'));
        $this->artisan('certification:reconcile')->assertSuccessful();
        $this->artisan('certification:reconcile')->assertSuccessful();

        $this->assertSame(2, ReservationCertificate::query()->count());
        $this->assertSame(CertificateStatus::Sent, $this->certificateOf($confirmed)->status);
        $this->assertSame(CertificateStatus::Sent, $this->certificateOf($secondConfirmed)->status);
        Notification::assertSentOnDemandTimes(VgpCertificateNotification::class, 2);
    }

    public function test_a_reservation_whose_event_was_lost_is_caught_up_within_a_minute(): void
    {
        $this->travelTo(CarbonImmutable::parse('2026-11-03 10:00:00'));
        $reservation = Reservation::factory()->for($this->machineWithReport())->for($this->customerWithEmail())->create();

        $this->travel(1)->minutes();
        $this->artisan('certification:reconcile')->assertSuccessful();

        $this->assertSame(CertificateStatus::Sent, $this->certificateOf($reservation)->status);
        $this->assertSame(ReservationStatus::Confirmed, $reservation->fresh()?->status);
    }

    public function test_without_go_live_date_the_catch_up_fails_explicitly(): void
    {
        config(['certification.go_live_date' => null]);

        $this->expectException(MissingGoLiveDateException::class);
        $this->artisan('certification:reconcile');
    }
}
