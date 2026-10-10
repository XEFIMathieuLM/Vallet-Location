<?php

namespace Functional\Portal\Tests\Feature\Staff;

use Carbon\CarbonImmutable;
use Functional\Fleet\Models\Agency;
use Functional\Portal\Actions\ConfirmReservationRequest;
use Functional\Portal\Actions\RefuseReservationRequest;
use Functional\Portal\Data\CustomerChoice;
use Functional\Portal\Enums\ReservationRequestStatus;
use Functional\Portal\Jobs\NotifyRequestDecisionJob;
use Functional\Portal\Mail\ReservationRequestConfirmedMail;
use Functional\Portal\Mail\ReservationRequestRefusedMail;
use Functional\Portal\Models\CustomerAccount;
use Functional\Portal\Notifications\ReservationRequestDecided;
use Functional\Portal\Tests\Concerns\BuildsPortalFixtures;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use RuntimeException;
use Tests\TestCase;

class RequestDecisionMailTest extends TestCase
{
    use BuildsPortalFixtures, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->travelTo(CarbonImmutable::parse('2026-10-10 09:00', 'Europe/Paris'));
        $this->seedPermissions();
    }

    public function test_the_customer_is_told_of_the_confirmation_with_the_reserved_machine_dates_and_pickup_agency(): void
    {
        Notification::fake();
        $account = $this->customerAccount();
        $machine = $this->reservableMachine(agency: Agency::factory()->create(['name' => 'Rouen', 'address' => '12 quai de Seine, Rouen']), attributes: ['reference' => 'NAC-0042']);
        $reservationRequest = $this->pendingRequest($account, $machine, '2026-11-10', '2026-11-14');

        app(ConfirmReservationRequest::class)->handle($reservationRequest, $machine, CustomerChoice::create(), $this->requestHandler());

        Notification::assertSentTo($account, ReservationRequestDecided::class, function (ReservationRequestDecided $notification) use ($account): bool {
            $mail = $notification->toMail($account);
            $this->assertInstanceOf(ReservationRequestConfirmedMail::class, $mail);
            $mail->assertSeeInHtml('NAC-0042');
            $mail->assertSeeInHtml('10/11/2026');
            $mail->assertSeeInHtml('14/11/2026');
            $mail->assertSeeInHtml('Rouen');
            $mail->assertSeeInHtml('12 quai de Seine, Rouen');

            return true;
        });
        $this->assertNotNull($reservationRequest->fresh()?->customer_notified_at);
    }

    public function test_the_customer_is_told_of_the_refusal_and_its_reason(): void
    {
        Notification::fake();
        $account = $this->customerAccount();
        $reservationRequest = $this->pendingRequest($account, $this->reservableMachine(), '2026-11-10', '2026-11-14');

        app(RefuseReservationRequest::class)->handle($reservationRequest, 'Machine partie en révision', $this->requestHandler());

        Notification::assertSentTo($account, ReservationRequestDecided::class, function (ReservationRequestDecided $notification) use ($account): bool {
            $mail = $notification->toMail($account);
            $this->assertInstanceOf(ReservationRequestRefusedMail::class, $mail);
            $mail->assertSeeInHtml('Machine partie en révision');

            return true;
        });
    }

    public function test_the_decision_is_recorded_even_when_the_mail_cannot_be_sent(): void
    {
        Notification::shouldReceive('send')->andThrow(new RuntimeException('SMTP indisponible'));
        $reservationRequest = $this->pendingRequest($this->customerAccount(), $this->reservableMachine(), '2026-11-10', '2026-11-14');

        rescue(fn () => app(RefuseReservationRequest::class)->handle($reservationRequest, 'Machine partie en révision', $this->requestHandler()), report: false);

        $refused = $reservationRequest->fresh();
        $this->assertSame(ReservationRequestStatus::Refused, $refused?->status);
        $this->assertNull($refused->customer_notified_at);
    }

    public function test_a_second_run_of_the_job_sends_nothing(): void
    {
        Notification::fake();
        $account = $this->customerAccount();
        $reservationRequest = $this->pendingRequest($account, $this->reservableMachine(), '2026-11-10', '2026-11-14');
        app(RefuseReservationRequest::class)->handle($reservationRequest, 'Machine partie en révision', $this->requestHandler());

        (new NotifyRequestDecisionJob($reservationRequest->id))->handle();

        Notification::assertSentToTimes($account, ReservationRequestDecided::class, 1);
    }

    public function test_a_pending_request_is_never_notified(): void
    {
        Notification::fake();
        $reservationRequest = $this->pendingRequest(CustomerAccount::factory()->create(), $this->reservableMachine(), '2026-11-10', '2026-11-14');

        (new NotifyRequestDecisionJob($reservationRequest->id))->handle();

        Notification::assertNothingSent();
        $this->assertNull($reservationRequest->fresh()?->customer_notified_at);
    }
}
