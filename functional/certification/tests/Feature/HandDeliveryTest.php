<?php

namespace Functional\Certification\Tests\Feature;

use Functional\Booking\Actions\DepartReservation;
use Functional\Booking\Enums\ReservationStatus;
use Functional\Booking\Models\Customer;
use Functional\Certification\Enums\CertificateStatus;
use Functional\Certification\Enums\DispatchChannel;
use Functional\Certification\Livewire\HandDeliveryButton;
use Functional\Certification\Tests\Concerns\BuildsCertificateScenarios;
use Functional\Fleet\Models\Machine;
use Functional\Fleet\Tests\Concerns\CreatesUsers;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;
use Spatie\Activitylog\Models\Activity;
use Tests\TestCase;

class HandDeliveryTest extends TestCase
{
    use BuildsCertificateScenarios, CreatesUsers, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->setUpCertificationScenario();
        $this->keepOnlyTheCertificateGuard();
        Notification::fake();
    }

    public function test_recording_the_hand_delivery_unblocks_the_departure(): void
    {
        $reservation = $this->reservationStartingToday($this->machineWithReport(), Customer::factory()->create(['email' => null]));

        Livewire::test(HandDeliveryButton::class, ['reservation' => $reservation])->call('record')->assertHasNoErrors();

        $certificate = $this->certificateOf($reservation);
        $this->assertSame(CertificateStatus::HandDelivered, $certificate->status);
        $this->assertNotNull($certificate->delivered_at);
        $dispatch = $certificate->dispatches()->sole();
        $this->assertSame(DispatchChannel::Hand, $dispatch->channel);
        $this->assertSame($this->employee->getAuthIdentifier(), $dispatch->author_id);
        $this->assertNotNull($dispatch->vgp_report_id);
        $this->assertTrue(Activity::query()->inLog('certification')->whereMorphedTo('subject', $reservation)->where('event', 'certificate_hand_delivered')->exists());

        app(DepartReservation::class)->handle($reservation);
        $this->assertSame(ReservationStatus::InProgress, $reservation->fresh()?->status);
    }

    public function test_without_report_there_is_nothing_to_hand_over(): void
    {
        $reservation = $this->reservationStartingToday(Machine::factory()->vgpValid()->create(), Customer::factory()->create(['email' => null]));

        Livewire::test(HandDeliveryButton::class, ['reservation' => $reservation])->call('record')->assertHasErrors('refusal');

        $this->assertSame(CertificateStatus::AwaitingReport, $this->certificateOf($reservation)->status);
    }

    public function test_the_hand_delivery_is_refused_on_a_reservation_not_confirmed_or_already_delivered(): void
    {
        $cancelled = $this->reservationStartingToday($this->machineWithReport(), Customer::factory()->create(['email' => null]));
        $cancelled->update(['status' => ReservationStatus::Cancelled]);
        $delivered = $this->reservationStartingToday($this->machineWithReport(), $this->customerWithEmail());

        Livewire::test(HandDeliveryButton::class, ['reservation' => $cancelled])->call('record')->assertHasErrors('refusal');
        Livewire::test(HandDeliveryButton::class, ['reservation' => $delivered])->call('record')->assertHasErrors('refusal');

        $this->assertSame(CertificateStatus::AwaitingEmail, $this->certificateOf($cancelled)->status);
        $this->assertSame(CertificateStatus::Sent, $this->certificateOf($delivered)->status);
    }
}
