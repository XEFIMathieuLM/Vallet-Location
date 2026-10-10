<?php

namespace Functional\Certification\Tests\Feature;

use Functional\Booking\Actions\DepartReservation;
use Functional\Booking\Enums\ReservationStatus;
use Functional\Booking\Models\Customer;
use Functional\Certification\Enums\CertificateStatus;
use Functional\Certification\Livewire\ResendCertificateButton;
use Functional\Certification\Livewire\ReservationCertificateSection;
use Functional\Certification\Models\VgpReport;
use Functional\Certification\Notifications\VgpCertificateNotification;
use Functional\Certification\Tests\Concerns\BuildsCertificateScenarios;
use Functional\Certification\Tests\Concerns\SimulatesMailTransport;
use Functional\Certification\Tests\Doubles\SimulatedMailTransport;
use Functional\Fleet\Models\Machine;
use Functional\Fleet\Tests\Concerns\CreatesUsers;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;
use Spatie\Activitylog\Models\Activity;
use Tests\TestCase;

class ResendCertificateTest extends TestCase
{
    use BuildsCertificateScenarios, CreatesUsers, RefreshDatabase, SimulatesMailTransport;

    protected function setUp(): void
    {
        parent::setUp();

        $this->setUpCertificationScenario();
        $this->keepOnlyTheCertificateGuard();
    }

    public function test_resending_on_a_confirmed_then_ongoing_reservation_sends_again_and_traces_the_author(): void
    {
        Notification::fake();
        $reservation = $this->reservationStartingToday($this->machineWithReport(), $this->customerWithEmail());

        Livewire::test(ResendCertificateButton::class, ['reservation' => $reservation])->call('resend')->assertHasNoErrors();
        app(DepartReservation::class)->handle($reservation);
        Livewire::test(ResendCertificateButton::class, ['reservation' => $reservation->fresh()])->call('resend')->assertHasNoErrors();

        Notification::assertSentOnDemandTimes(VgpCertificateNotification::class, 3);
        $manualDispatches = $this->certificateOf($reservation)->dispatches()->where('is_automatic', false)->get();
        $this->assertCount(2, $manualDispatches);
        $this->assertSame($this->employee->getAuthIdentifier(), $manualDispatches->first()?->author_id);
        $this->assertSame(3, Activity::query()->inLog('certification')->whereMorphedTo('subject', $reservation)->where('event', 'certificate_sent')->count());
    }

    public function test_the_resend_is_neither_offered_nor_accepted_on_a_cancelled_or_closed_reservation(): void
    {
        Notification::fake();
        $reservation = $this->reservationStartingToday($this->machineWithReport(), $this->customerWithEmail());
        $reservation->update(['status' => ReservationStatus::Cancelled]);

        Livewire::test(ReservationCertificateSection::class, ['reservation' => $reservation])->assertDontSee('Renvoyer l\'attestation');
        Livewire::test(ResendCertificateButton::class, ['reservation' => $reservation])->call('resend')->assertHasErrors('refusal');

        $reservation->update(['status' => ReservationStatus::Closed]);
        Livewire::test(ResendCertificateButton::class, ['reservation' => $reservation])->call('resend')->assertHasErrors('refusal');
        Notification::assertSentOnDemandTimes(VgpCertificateNotification::class, 1);
    }

    public function test_the_resend_attaches_the_report_in_force_at_the_time_of_resending(): void
    {
        Notification::fake();
        $machine = $this->machineWithReport();
        $reservation = $this->reservationStartingToday($machine, $this->customerWithEmail());
        $newReport = VgpReport::factory()->for($machine)->create();

        Livewire::test(ResendCertificateButton::class, ['reservation' => $reservation])->call('resend');

        Notification::assertSentOnDemand(VgpCertificateNotification::class, fn (VgpCertificateNotification $notification): bool => $notification->report->is($newReport));
    }

    public function test_the_resend_is_refused_without_report_or_without_email(): void
    {
        Notification::fake();
        $withoutReport = $this->reservationStartingToday(Machine::factory()->vgpValid()->create(), $this->customerWithEmail());
        $withoutEmail = $this->reservationStartingToday($this->machineWithReport(), Customer::factory()->create(['email' => null]));

        Livewire::test(ResendCertificateButton::class, ['reservation' => $withoutReport])->call('resend')->assertHasErrors('refusal');
        Livewire::test(ResendCertificateButton::class, ['reservation' => $withoutEmail])->call('resend')->assertHasErrors('refusal');
        Notification::assertNothingSent();
    }

    public function test_a_failing_resend_shows_its_reason_and_keeps_a_delivered_certificate_delivered(): void
    {
        $this->simulateMailTransport();
        $reservation = $this->reservationStartingToday($this->machineWithReport(), $this->customerWithEmail());
        $this->switchMailTransportTo(SimulatedMailTransport::REJECTS_RECIPIENT);

        Livewire::test(ResendCertificateButton::class, ['reservation' => $reservation])->call('resend')->assertHasErrors('refusal')->assertSee('refusée par la messagerie');

        $this->assertSame(CertificateStatus::Sent, $this->certificateOf($reservation)->status);
        $this->assertSame(1, $this->certificateOf($reservation)->dispatches()->where('outcome', 'failed')->count());
    }

    public function test_a_successful_resend_delivers_a_certificate_that_was_not_delivered(): void
    {
        $this->simulateMailTransport(SimulatedMailTransport::REJECTS_RECIPIENT);
        $reservation = $this->reservationStartingToday($this->machineWithReport(), $this->customerWithEmail());
        $this->assertSame(CertificateStatus::Failed, $this->certificateOf($reservation)->status);
        $this->switchMailTransportTo(SimulatedMailTransport::ACCEPTS);

        Livewire::test(ResendCertificateButton::class, ['reservation' => $reservation])->call('resend')->assertHasNoErrors();

        $this->assertSame(CertificateStatus::Sent, $this->certificateOf($reservation)->status);
        $this->assertSame(1, $this->sentMailCount());
    }
}
