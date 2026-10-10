<?php

namespace Functional\Portal\Tests\Feature\Customer;

use Carbon\CarbonImmutable;
use Functional\Booking\Models\Reservation;
use Functional\Fleet\Tests\Concerns\AssertsRefusals;
use Functional\Portal\Actions\CancelReservationRequest;
use Functional\Portal\Enums\PortalHistoryEvent;
use Functional\Portal\Enums\ReservationRequestStatus;
use Functional\Portal\Events\ReservationRequestChanged;
use Functional\Portal\Exceptions\IllegalReservationRequestTransitionException;
use Functional\Portal\Livewire\Customer\MyRequests;
use Functional\Portal\Models\CustomerAccount;
use Functional\Portal\Models\ReservationRequest;
use Functional\Portal\Queries\PendingRequests;
use Functional\Portal\Tests\Concerns\BuildsPortalFixtures;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;
use Spatie\Activitylog\Models\Activity;
use Tests\TestCase;

class MyRequestsTest extends TestCase
{
    use AssertsRefusals, BuildsPortalFixtures, RefreshDatabase;

    private CustomerAccount $account;

    protected function setUp(): void
    {
        parent::setUp();
        $this->travelTo(CarbonImmutable::parse('2026-10-10 09:00', 'Europe/Paris'));
        $this->account = $this->customerAccount();
        $this->actingAs($this->account, 'customer');
    }

    public function test_the_customer_sees_each_request_with_its_status_and_outcome(): void
    {
        $this->pendingRequest($this->account, $this->reservableMachine(attributes: ['reference' => 'NAC-ATTENTE']), '2026-11-10', '2026-11-12');
        ReservationRequest::factory()->for($this->account, 'account')->refused('Machine partie en révision')->create();
        $reservation = Reservation::factory()->create();
        ReservationRequest::factory()->for($this->account, 'account')->confirmed($reservation)->create();
        ReservationRequest::factory()->for($this->account, 'account')->expired()->create();
        ReservationRequest::factory()->for($this->account, 'account')->cancelled()->create();

        $this->get(route('portal.requests'))->assertOk();
        Livewire::test(MyRequests::class)
            ->assertSee(['NAC-ATTENTE', '10/11/2026', '12/11/2026'])
            ->assertSee([__('portal::requests.statuses.pending'), __('portal::requests.statuses.refused'), __('portal::requests.statuses.confirmed'), __('portal::requests.statuses.expired'), __('portal::requests.statuses.cancelled')])
            ->assertSee('Machine partie en révision')
            ->assertSee($reservation->machine->reference)
            ->assertSee(__('portal::requests.cancel'));
    }

    public function test_the_customer_cancels_a_pending_request(): void
    {
        Event::fake([ReservationRequestChanged::class]);
        Notification::fake();
        $reservationRequest = $this->pendingRequest($this->account, $this->reservableMachine(), '2026-11-10', '2026-11-12');

        Livewire::test(MyRequests::class)->call('cancel', $reservationRequest->id)->assertHasNoErrors();

        $cancelled = $reservationRequest->fresh();
        $this->assertSame(ReservationRequestStatus::Cancelled, $cancelled?->status);
        $this->assertNotNull($cancelled->decided_at);
        $this->assertNull($cancelled->decided_by);
        $this->assertSame(0, app(PendingRequests::class)->count());
        $this->assertTrue(Activity::query()->where('event', PortalHistoryEvent::RequestCancelled->value)->whereMorphedTo('subject', $cancelled)->exists());
        Event::assertDispatched(ReservationRequestChanged::class);
        Notification::assertNothingSent();
    }

    public function test_a_request_that_is_no_longer_pending_cannot_be_cancelled(): void
    {
        foreach (['confirmed', 'refused', 'expired'] as $decision) {
            $reservationRequest = ReservationRequest::factory()->for($this->account, 'account')->{$decision}()->create();

            $this->assertRefused(IllegalReservationRequestTransitionException::class, 'Impossible', fn () => app(CancelReservationRequest::class)->handle($reservationRequest, $this->account));
            $this->assertSame($decision, $reservationRequest->fresh()?->status->value);
        }
    }
}
