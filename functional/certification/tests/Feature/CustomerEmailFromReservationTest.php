<?php

namespace Functional\Certification\Tests\Feature;

use Functional\Booking\Actions\UpdateCustomer;
use Functional\Booking\Events\CustomerChanged;
use Functional\Booking\Models\Customer;
use Functional\Booking\Models\Reservation;
use Functional\Certification\Enums\CertificateStatus;
use Functional\Certification\Enums\DispatchFailureReason;
use Functional\Certification\Livewire\CustomerEmailForm;
use Functional\Certification\Models\CertificateDispatch;
use Functional\Certification\Models\ReservationCertificate;
use Functional\Certification\Notifications\VgpCertificateNotification;
use Functional\Certification\Tests\Concerns\BuildsCertificateScenarios;
use Functional\Fleet\Tests\Concerns\CreatesUsers;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Notifications\AnonymousNotifiable;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;
use Spatie\Activitylog\Models\Activity;
use Tests\TestCase;

class CustomerEmailFromReservationTest extends TestCase
{
    use BuildsCertificateScenarios, CreatesUsers, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->setUpCertificationScenario();
        Notification::fake();
    }

    public function test_entering_the_email_sends_every_certificate_waiting_for_it(): void
    {
        $customer = Customer::factory()->create(['email' => null]);
        $machine = $this->machineWithReport();
        $reservation = $this->reserve($machine, $customer);
        $otherReservation = $this->reserve($machine, $customer, startInDays: 12);

        Livewire::test(CustomerEmailForm::class, ['reservation' => $reservation])
            ->set('email', 'chantier@exemple.fr')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertSame(CertificateStatus::Sent, $this->certificateOf($reservation)->status);
        $this->assertSame(CertificateStatus::Sent, $this->certificateOf($otherReservation)->status);
        Notification::assertSentOnDemand(VgpCertificateNotification::class, fn (VgpCertificateNotification $notification, array $channels, AnonymousNotifiable $notifiable): bool => $notifiable->routes['mail'] === 'chantier@exemple.fr');
        $activity = Activity::query()->inLog('certification')->whereMorphedTo('subject', $reservation)->where('event', 'customer_email_updated')->sole();
        $this->assertStringContainsString('chantier@exemple.fr', $activity->description);
    }

    public function test_an_invalid_email_is_refused(): void
    {
        $reservation = $this->reserve($this->machineWithReport(), Customer::factory()->create(['email' => null]));

        Livewire::test(CustomerEmailForm::class, ['reservation' => $reservation])
            ->set('email', 'pas-une-adresse')
            ->call('save')
            ->assertHasErrors('refusal');

        $this->assertSame(CertificateStatus::AwaitingEmail, $this->certificateOf($reservation)->status);
    }

    public function test_a_failed_certificate_is_not_resent_when_the_email_is_unchanged(): void
    {
        $customer = $this->customerWithEmail('ancienne@exemple.fr');
        $reservation = Reservation::factory()->for($this->machineWithReport())->for($customer)->create();
        $certificate = ReservationCertificate::factory()->for($reservation)->failed(DispatchFailureReason::InvalidAddress)->create();
        CertificateDispatch::factory()->for($certificate, 'certificate')->failed()->create(['recipient_email' => 'ancienne@exemple.fr']);

        event(new CustomerChanged($customer, ['type']));
        $this->assertSame(CertificateStatus::Failed, $certificate->fresh()?->status);

        event(new CustomerChanged($customer, ['email']));
        $this->assertSame(CertificateStatus::Failed, $certificate->fresh()?->status);

        app(UpdateCustomer::class)->changeEmail($customer, 'nouvelle@exemple.fr', $this->employee);
        $this->assertSame(CertificateStatus::Sent, $certificate->fresh()?->status);
        Notification::assertSentOnDemandTimes(VgpCertificateNotification::class, 1);
    }
}
