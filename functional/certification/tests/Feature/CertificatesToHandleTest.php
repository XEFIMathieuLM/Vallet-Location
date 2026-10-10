<?php

namespace Functional\Certification\Tests\Feature;

use Carbon\CarbonImmutable;
use Functional\Booking\Enums\ReservationStatus;
use Functional\Booking\Models\Customer;
use Functional\Booking\Models\Reservation;
use Functional\Certification\Enums\CertificateStatus;
use Functional\Certification\Enums\DispatchFailureReason;
use Functional\Certification\Livewire\CertificatesToHandle as CertificatesToHandleScreen;
use Functional\Certification\Livewire\CertificationAlert;
use Functional\Certification\Models\ReservationCertificate;
use Functional\Certification\Queries\CertificatesToHandle;
use Functional\Certification\Tests\Concerns\BuildsCertificateScenarios;
use Functional\Fleet\Models\Machine;
use Functional\Fleet\Tests\Concerns\CreatesUsers;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Tests\TestCase;

class CertificatesToHandleTest extends TestCase
{
    use BuildsCertificateScenarios, CreatesUsers, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->setUpCertificationScenario();
    }

    public function test_the_list_holds_exactly_the_certificates_that_did_not_reach_the_customer(): void
    {
        $failed = $this->certificate(CertificateStatus::Failed, startInDays: 5);
        $awaitingEmail = $this->certificate(CertificateStatus::AwaitingEmail, startInDays: 2);
        $awaitingReport = $this->certificate(CertificateStatus::AwaitingReport, startInDays: 9);
        $stalePending = $this->certificate(CertificateStatus::Pending, startInDays: 7, minutesInStatus: 61);
        $this->certificate(CertificateStatus::Pending, startInDays: 4, minutesInStatus: 5);
        $this->certificate(CertificateStatus::Sent, startInDays: 3);
        $this->certificate(CertificateStatus::HandDelivered, startInDays: 3);
        $this->certificate(CertificateStatus::Failed, startInDays: 6, reservationStatus: ReservationStatus::Cancelled);
        $this->certificate(CertificateStatus::AwaitingEmail, startInDays: 6, reservationStatus: ReservationStatus::InProgress);

        $handledIds = app(CertificatesToHandle::class)->query()->pluck('reservation_certificates.id')->all();

        $this->assertSame([$awaitingEmail->id, $failed->id, $stalePending->id, $awaitingReport->id], $handledIds);
    }

    public function test_a_certificate_long_awaiting_its_email_and_just_queued_is_not_listed(): void
    {
        $certificate = $this->certificate(CertificateStatus::AwaitingEmail, startInDays: 3, minutesInStatus: 3000);
        $certificate->update(['status' => CertificateStatus::Pending, 'status_changed_at' => CarbonImmutable::now()->subMinutes(5)]);

        $this->assertSame(0, app(CertificatesToHandle::class)->query()->count());
    }

    public function test_the_screen_explains_each_certificate_and_the_alert_counts_them(): void
    {
        $failed = $this->certificate(CertificateStatus::Failed, startInDays: 5);
        $failed->reservation->customer->update(['name' => 'BTP Normandie', 'email' => 'erreur@exemple']);
        $failed->reservation->machine->update(['reference' => 'NAC-0099']);

        $this->get(route('certification.certificates'))->assertOk()
            ->assertSee('NAC-0099')->assertSee('BTP Normandie')->assertSee('erreur@exemple')->assertSee('Adresse e-mail invalide')
            ->assertSee($failed->reservation->start_date->format('d/m/Y'));

        Livewire::test(CertificationAlert::class)->assertSee('1 attestation VGP à traiter');
        $this->get(route('dashboard'))->assertSee('1 attestation VGP à traiter');
    }

    public function test_the_screen_runs_a_constant_number_of_queries(): void
    {
        foreach (range(1, 3) as $startInDays) {
            $this->certificate(CertificateStatus::Failed, startInDays: $startInDays);
        }
        $fewQueries = $this->queriesToRender();

        foreach (range(4, 20) as $startInDays) {
            $this->certificate(CertificateStatus::Failed, startInDays: $startInDays);
        }

        $this->assertSame($fewQueries, $this->queriesToRender());
    }

    private function certificate(CertificateStatus $status, int $startInDays, int $minutesInStatus = 0, ReservationStatus $reservationStatus = ReservationStatus::Confirmed): ReservationCertificate
    {
        $startDate = CarbonImmutable::today()->addDays($startInDays);
        $reservation = Reservation::factory()->for(Machine::factory()->vgpValid())->for(Customer::factory()->create(['email' => 'client@exemple.fr']))
            ->between($startDate, $startDate->addDays(2))->withStatus($reservationStatus)->create();

        $certificate = ReservationCertificate::factory()->for($reservation)->withStatus($status)->create([
            'status_changed_at' => CarbonImmutable::now()->subMinutes($minutesInStatus),
            'last_failure_reason' => $status === CertificateStatus::Failed ? DispatchFailureReason::InvalidAddress : null,
        ]);

        return $certificate;
    }

    private function queriesToRender(): int
    {
        DB::flushQueryLog();
        DB::enableQueryLog();
        Livewire::test(CertificatesToHandleScreen::class);
        $queryCount = count(DB::getQueryLog());
        DB::disableQueryLog();

        return $queryCount;
    }
}
