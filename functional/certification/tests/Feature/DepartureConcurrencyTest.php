<?php

namespace Functional\Certification\Tests\Feature;

use Functional\Booking\Actions\DepartReservation;
use Functional\Booking\Enums\ReservationStatus;
use Functional\Certification\Actions\SendCertificate;
use Functional\Certification\Exceptions\CertificateNotDeliveredException;
use Functional\Certification\Tests\Concerns\BuildsCertificateScenarios;
use Functional\Fleet\Tests\Concerns\AssertsRefusals;
use Functional\Fleet\Tests\Concerns\CreatesUsers;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class DepartureConcurrencyTest extends TestCase
{
    use AssertsRefusals, BuildsCertificateScenarios, CreatesUsers, RefreshDatabase;

    public function test_the_guard_reads_the_certificate_under_lock_and_follows_the_committed_sending(): void
    {
        $this->setUpCertificationScenario();
        $this->keepOnlyTheCertificateGuard();
        Notification::fake();
        Queue::fake();
        $reservation = $this->reservationStartingToday($this->machineWithReport(), $this->customerWithEmail());
        $lockedReads = [];
        DB::listen(function (QueryExecuted $query) use (&$lockedReads): void {
            if (str_contains($query->sql, 'reservation_certificates') && str_contains($query->sql, 'for update')) {
                $lockedReads[] = $query->sql;
            }
        });

        $this->assertRefused(CertificateNotDeliveredException::class, 'envoi en attente', fn () => app(DepartReservation::class)->handle($reservation));
        $this->assertNotEmpty($lockedReads);

        app(SendCertificate::class)->handle($this->certificateOf($reservation)->id);
        app(DepartReservation::class)->handle($reservation);

        $this->assertSame(ReservationStatus::InProgress, $reservation->fresh()?->status);
    }
}
