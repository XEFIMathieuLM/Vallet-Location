<?php

namespace Functional\Portal\Tests\Feature;

use Carbon\CarbonImmutable;
use Functional\Portal\Enums\PortalHistoryEvent;
use Functional\Portal\Enums\ReservationRequestStatus;
use Functional\Portal\Jobs\NotifyRequestDecisionJob;
use Functional\Portal\Mail\ReservationRequestExpiredMail;
use Functional\Portal\Models\ReservationRequest;
use Functional\Portal\Notifications\ReservationRequestDecided;
use Functional\Portal\Tests\Concerns\BuildsPortalFixtures;
use Illuminate\Console\Scheduling\Event as ScheduledEvent;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Queue;
use Spatie\Activitylog\Models\Activity;
use Tests\TestCase;

class ExpireReservationRequestsTest extends TestCase
{
    use BuildsPortalFixtures, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Notification::fake();
    }

    public function test_a_pending_request_whose_start_date_has_passed_expires_and_the_customer_is_told(): void
    {
        $this->travelTo(CarbonImmutable::parse('2026-11-09 18:00', 'Europe/Paris'));
        $account = $this->customerAccount();
        $yesterday = $this->pendingRequest($account, $this->reservableMachine(), '2026-11-09', '2026-11-12');
        $today = $this->pendingRequest($account, $this->reservableMachine(), '2026-11-10', '2026-11-12');
        $this->travelTo(CarbonImmutable::parse('2026-11-10 00:05', 'Europe/Paris'));

        $this->artisan('portal:reconcile')->assertSuccessful();

        $expired = $yesterday->fresh();
        $this->assertSame(ReservationRequestStatus::Expired, $expired?->status);
        $this->assertNotNull($expired->decided_at);
        $this->assertSame(ReservationRequestStatus::Pending, $today->fresh()?->status);
        $activity = Activity::query()->where('event', PortalHistoryEvent::RequestExpired->value)->whereMorphedTo('subject', $expired)->sole();
        $this->assertNull($activity->causer_id);
        Notification::assertSentTo($account, ReservationRequestDecided::class, fn (ReservationRequestDecided $notification): bool => $notification->toMail($account) instanceof ReservationRequestExpiredMail);
    }

    public function test_the_command_can_run_again_without_expiring_or_mailing_twice(): void
    {
        $this->travelTo(CarbonImmutable::parse('2026-11-01 10:00', 'Europe/Paris'));
        $account = $this->customerAccount();
        $this->pendingRequest($account, $this->reservableMachine(), '2026-11-03', '2026-11-04');
        $this->travelTo(CarbonImmutable::parse('2026-11-05 10:00', 'Europe/Paris'));

        $this->artisan('portal:reconcile')->assertSuccessful();
        $this->artisan('portal:reconcile')->assertSuccessful();

        $this->assertSame(1, Activity::query()->where('event', PortalHistoryEvent::RequestExpired->value)->count());
        Notification::assertSentToTimes($account, ReservationRequestDecided::class, 1);
    }

    public function test_a_decision_whose_mail_never_left_is_queued_again_after_ten_minutes(): void
    {
        $this->travelTo(CarbonImmutable::parse('2026-11-01 10:00', 'Europe/Paris'));
        $forgotten = ReservationRequest::factory()->refused()->create(['decided_at' => now()->subMinutes(11)]);
        ReservationRequest::factory()->refused()->create(['decided_at' => now()->subMinutes(5)]);
        ReservationRequest::factory()->refused()->create(['decided_at' => now()->subHour(), 'customer_notified_at' => now()->subHour()]);
        ReservationRequest::factory()->cancelled()->create(['decided_at' => now()->subHour()]);
        Queue::fake();

        $this->artisan('portal:reconcile')->assertSuccessful();

        Queue::assertPushed(NotifyRequestDecisionJob::class, 1);
        Queue::assertPushed(NotifyRequestDecisionJob::class, fn (NotifyRequestDecisionJob $job): bool => $job->reservationRequestId === $forgotten->id);
    }

    public function test_the_reconciliation_runs_every_five_minutes(): void
    {
        $reconciliation = collect(app(Schedule::class)->events())
            ->first(fn (ScheduledEvent $event): bool => str_contains((string) $event->command, 'portal:reconcile'));

        $this->assertNotNull($reconciliation);
        $this->assertSame('*/5 * * * *', $reconciliation->expression);
        $this->assertTrue($reconciliation->withoutOverlapping);
    }
}
