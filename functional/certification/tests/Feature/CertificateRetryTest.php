<?php

namespace Functional\Certification\Tests\Feature;

use Carbon\CarbonImmutable;
use Functional\Booking\Enums\ReservationStatus;
use Functional\Certification\Enums\CertificateStatus;
use Functional\Certification\Enums\DispatchFailureReason;
use Functional\Certification\Enums\DispatchOutcome;
use Functional\Certification\Tests\Concerns\BuildsCertificateScenarios;
use Functional\Certification\Tests\Concerns\SimulatesMailTransport;
use Functional\Certification\Tests\Doubles\SimulatedMailTransport;
use Functional\Fleet\Tests\Concerns\CreatesUsers;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CertificateRetryTest extends TestCase
{
    use BuildsCertificateScenarios, CreatesUsers, RefreshDatabase, SimulatesMailTransport;

    protected function setUp(): void
    {
        parent::setUp();

        $this->setUpCertificationScenario();
        $this->travelTo(CarbonImmutable::parse('2026-11-02 09:00:00'));
        config(['certification.go_live_date' => '2026-11-01']);
    }

    public function test_an_unavailable_mail_service_keeps_the_certificate_pending_and_schedules_a_retry(): void
    {
        $this->simulateMailTransport(SimulatedMailTransport::UNAVAILABLE);

        $reservation = $this->reserve($this->machineWithReport(), $this->customerWithEmail());

        $this->assertSame(ReservationStatus::Confirmed, $reservation->status);
        $certificate = $this->certificateOf($reservation);
        $this->assertSame(CertificateStatus::Pending, $certificate->status);
        $this->assertSame(1, $certificate->attempts);
        $this->assertSame('2026-11-02 09:01:00', $certificate->next_attempt_at?->toDateTimeString());
        $dispatch = $certificate->dispatches()->sole();
        $this->assertSame(DispatchOutcome::Failed, $dispatch->outcome);
        $this->assertSame(DispatchFailureReason::MailServiceUnavailable, $dispatch->failure_reason);
    }

    public function test_retries_follow_the_configured_delays_then_every_hour(): void
    {
        $this->simulateMailTransport(SimulatedMailTransport::UNAVAILABLE);
        $certificate = $this->certificateOf($this->reserve($this->machineWithReport(), $this->customerWithEmail()));

        foreach ([5, 15, 60, 60] as $expectedDelay) {
            $this->travelTo($certificate->fresh()?->next_attempt_at);
            $this->artisan('certification:reconcile')->assertSuccessful();
            $nextAttemptAt = $certificate->fresh()?->next_attempt_at;

            $this->assertSame($expectedDelay, (int) CarbonImmutable::now()->diffInMinutes($nextAttemptAt));
        }

        $this->assertSame(5, $certificate->fresh()?->attempts);
    }

    public function test_once_the_mail_service_is_back_the_certificate_is_sent_only_once(): void
    {
        $this->simulateMailTransport(SimulatedMailTransport::UNAVAILABLE);
        $certificate = $this->certificateOf($this->reserve($this->machineWithReport(), $this->customerWithEmail()));
        $this->switchMailTransportTo(SimulatedMailTransport::ACCEPTS);

        $this->artisan('certification:reconcile')->assertSuccessful();
        $this->assertSame(CertificateStatus::Pending, $certificate->fresh()?->status);

        $this->travel(1)->minutes();
        $this->artisan('certification:reconcile')->assertSuccessful();
        $this->artisan('certification:reconcile')->assertSuccessful();

        $this->assertSame(CertificateStatus::Sent, $certificate->fresh()?->status);
        $this->assertSame(1, $this->sentMailCount());
    }

    public function test_a_rejected_recipient_fails_without_retry(): void
    {
        $this->simulateMailTransport(SimulatedMailTransport::REJECTS_RECIPIENT);
        $certificate = $this->certificateOf($this->reserve($this->machineWithReport(), $this->customerWithEmail()));

        $this->assertSame(CertificateStatus::Failed, $certificate->status);
        $this->assertSame(DispatchFailureReason::RecipientRejected, $certificate->last_failure_reason);
        $this->assertNull($certificate->next_attempt_at);

        $this->travel(2)->hours();
        $this->switchMailTransportTo(SimulatedMailTransport::ACCEPTS);
        $this->artisan('certification:reconcile')->assertSuccessful();
        $this->assertSame(0, $this->sentMailCount());
    }
}
