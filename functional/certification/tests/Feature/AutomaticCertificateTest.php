<?php

namespace Functional\Certification\Tests\Feature;

use Functional\Booking\Actions\CancelReservation;
use Functional\Booking\Enums\ReservationStatus;
use Functional\Booking\Models\Customer;
use Functional\Certification\Actions\SendCertificate;
use Functional\Certification\Enums\CertificateStatus;
use Functional\Certification\Enums\DispatchChannel;
use Functional\Certification\Enums\DispatchOutcome;
use Functional\Certification\Jobs\SendCertificateJob;
use Functional\Certification\Models\ReservationCertificate;
use Functional\Certification\Notifications\VgpCertificateNotification;
use Functional\Certification\Tests\Concerns\BuildsCertificateScenarios;
use Functional\Fleet\Models\Machine;
use Functional\Fleet\Tests\Concerns\CreatesUsers;
use Illuminate\Events\CallQueuedListener;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Notifications\AnonymousNotifiable;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Queue;
use Spatie\Activitylog\Models\Activity;
use Tests\TestCase;

class AutomaticCertificateTest extends TestCase
{
    use BuildsCertificateScenarios, CreatesUsers, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->setUpCertificationScenario();
    }

    public function test_reserving_a_vgp_machine_sends_its_report_to_the_customer(): void
    {
        Notification::fake();

        $reservation = $this->reserve($this->machineWithReport(), $this->customerWithEmail());

        Notification::assertSentOnDemand(VgpCertificateNotification::class, fn (VgpCertificateNotification $notification, array $channels, AnonymousNotifiable $notifiable): bool => $notifiable->routes['mail'] === 'chantier@exemple.fr');
        $certificate = $this->certificateOf($reservation);
        $this->assertSame(CertificateStatus::Sent, $certificate->status);
        $this->assertNotNull($certificate->delivered_at);
        $dispatch = $certificate->dispatches()->sole();
        $this->assertSame(DispatchChannel::Email, $dispatch->channel);
        $this->assertSame(DispatchOutcome::Sent, $dispatch->outcome);
        $this->assertTrue($dispatch->is_automatic);
        $this->assertSame('chantier@exemple.fr', $dispatch->recipient_email);
    }

    public function test_a_machine_not_subject_to_vgp_gets_no_certificate(): void
    {
        Notification::fake();

        $this->reserve(Machine::factory()->create(['is_subject_to_vgp' => false]), $this->customerWithEmail());

        Notification::assertNothingSent();
        $this->assertSame(0, ReservationCertificate::query()->count());
    }

    public function test_the_reservation_is_confirmed_without_waiting_for_the_email(): void
    {
        Notification::fake();
        Queue::fake();

        $reservation = $this->reserve($this->machineWithReport(), $this->customerWithEmail());

        $this->assertSame(ReservationStatus::Confirmed, $reservation->status);
        Notification::assertNothingSent();
        Queue::assertPushed(CallQueuedListener::class);
    }

    public function test_a_reservation_cancelled_before_the_sending_gets_nothing(): void
    {
        $reservation = $this->reserve($this->machineWithReport(), Customer::factory()->create(['email' => null]));
        $certificate = $this->certificateOf($reservation);
        $certificate->update(['status' => CertificateStatus::Pending]);
        Notification::fake();

        app(CancelReservation::class)->handle($reservation);
        app(SendCertificate::class)->handle($certificate->id);

        Notification::assertNothingSent();
        $this->assertSame(CertificateStatus::Pending, $certificate->fresh()?->status);
    }

    public function test_the_sending_is_recorded_in_the_reservation_history(): void
    {
        Notification::fake();

        $reservation = $this->reserve($this->machineWithReport(), $this->customerWithEmail());

        $activity = Activity::query()->inLog('certification')->whereMorphedTo('subject', $reservation)->where('event', 'certificate_sent')->sole();
        $this->assertStringContainsString('chantier@exemple.fr', $activity->description);
        $this->assertNotNull($activity->properties->get('report_id'));
    }

    public function test_the_automatic_sending_happens_only_once(): void
    {
        Notification::fake();
        $reservation = $this->reserve($this->machineWithReport(), $this->customerWithEmail());
        $certificate = $this->certificateOf($reservation);

        app(SendCertificate::class)->handle($certificate->id);
        (new SendCertificateJob($certificate->id))->handle(app(SendCertificate::class));

        Notification::assertSentOnDemandTimes(VgpCertificateNotification::class, 1);
        $this->assertSame(1, $certificate->dispatches()->count());
    }
}
