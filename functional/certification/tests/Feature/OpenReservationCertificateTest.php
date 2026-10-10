<?php

namespace Functional\Certification\Tests\Feature;

use Carbon\CarbonImmutable;
use Functional\Booking\Enums\ReservationStatus;
use Functional\Booking\Models\Customer;
use Functional\Booking\Models\Reservation;
use Functional\Certification\Actions\OpenReservationCertificate;
use Functional\Certification\Enums\CertificateStatus;
use Functional\Certification\Jobs\SendCertificateJob;
use Functional\Certification\Models\ReservationCertificate;
use Functional\Certification\Models\VgpReport;
use Functional\Fleet\Models\Machine;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class OpenReservationCertificateTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('vgp-reports');
        Queue::fake();
        config(['certification.go_live_date' => CarbonImmutable::today()->toDateString()]);
    }

    public function test_a_ready_reservation_gets_one_pending_certificate_and_its_sending_is_queued(): void
    {
        $reservation = $this->reservationOf($this->machineWithReport(), Customer::factory()->reachableByEmail()->create());

        $certificate = app(OpenReservationCertificate::class)->handle($reservation);
        app(OpenReservationCertificate::class)->handle($reservation);

        $this->assertSame(CertificateStatus::Pending, $certificate?->status);
        $this->assertSame(1, ReservationCertificate::query()->count());
        Queue::assertPushed(SendCertificateJob::class, 1);
    }

    public function test_the_initial_status_says_what_is_missing(): void
    {
        $withoutReport = $this->reservationOf(Machine::factory()->vgpValid()->create(), Customer::factory()->reachableByEmail()->create());
        $withoutEmail = $this->reservationOf($this->machineWithReport(), Customer::factory()->create(['email' => null]));

        $this->assertSame(CertificateStatus::AwaitingReport, app(OpenReservationCertificate::class)->handle($withoutReport)?->status);
        $this->assertSame(CertificateStatus::AwaitingEmail, app(OpenReservationCertificate::class)->handle($withoutEmail)?->status);
        Queue::assertNotPushed(SendCertificateJob::class);
    }

    public function test_nothing_is_opened_when_the_reservation_is_not_concerned(): void
    {
        $customer = Customer::factory()->reachableByEmail()->create();
        $notSubjectToVgp = $this->reservationOf(Machine::factory()->create(['is_subject_to_vgp' => false]), $customer);
        $cancelled = Reservation::factory()->for($this->machineWithReport())->for($customer)->withStatus(ReservationStatus::Cancelled)->create();

        $this->assertNull(app(OpenReservationCertificate::class)->handle($notSubjectToVgp));
        $this->assertNull(app(OpenReservationCertificate::class)->handle($cancelled));
        $this->assertSame(0, ReservationCertificate::query()->count());
    }

    public function test_nothing_is_opened_before_the_go_live_date(): void
    {
        config(['certification.go_live_date' => CarbonImmutable::today()->addDay()->toDateString()]);
        $reservation = $this->reservationOf($this->machineWithReport(), Customer::factory()->reachableByEmail()->create());

        $this->assertNull(app(OpenReservationCertificate::class)->handle($reservation));
        $this->assertSame(0, ReservationCertificate::query()->count());
    }

    private function machineWithReport(): Machine
    {
        $machine = Machine::factory()->vgpValid()->create();
        VgpReport::factory()->for($machine)->create();

        return $machine;
    }

    private function reservationOf(Machine $machine, Customer $customer): Reservation
    {
        return Reservation::factory()->for($machine)->for($customer)->create();
    }
}
