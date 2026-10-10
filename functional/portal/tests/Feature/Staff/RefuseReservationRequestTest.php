<?php

namespace Functional\Portal\Tests\Feature\Staff;

use Carbon\CarbonImmutable;
use Functional\Fleet\Tests\Concerns\AssertsRefusals;
use Functional\Portal\Actions\RefuseReservationRequest;
use Functional\Portal\Enums\PortalHistoryEvent;
use Functional\Portal\Enums\ReservationRequestStatus;
use Functional\Portal\Exceptions\IllegalReservationRequestTransitionException;
use Functional\Portal\Exceptions\RefusalReasonRequiredException;
use Functional\Portal\Livewire\Staff\RefuseRequestModal;
use Functional\Portal\Tests\Concerns\BuildsPortalFixtures;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use Livewire\Livewire;
use Spatie\Activitylog\Models\Activity;
use Tests\TestCase;

class RefuseReservationRequestTest extends TestCase
{
    use AssertsRefusals, BuildsPortalFixtures, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->travelTo(CarbonImmutable::parse('2026-10-10 09:00', 'Europe/Paris'));
        Notification::fake();
        $this->seedPermissions();
    }

    public function test_an_employee_refuses_a_request_with_a_reason(): void
    {
        $handler = $this->requestHandler();
        $this->actingAs($handler);
        $reservationRequest = $this->pendingRequest($this->customerAccount(), $this->reservableMachine(), '2026-11-10', '2026-11-14');

        Livewire::test(RefuseRequestModal::class, ['reservationRequestId' => $reservationRequest->id])
            ->set('reason', 'Machine indisponible à ces dates')
            ->call('refuse')
            ->assertHasNoErrors()
            ->assertDispatched('reservation-request-decided');

        $refused = $reservationRequest->fresh();
        $this->assertSame(ReservationRequestStatus::Refused, $refused?->status);
        $this->assertSame('Machine indisponible à ces dates', $refused->refusal_reason);
        $this->assertSame($handler->getKey(), $refused->decided_by);
        $this->assertSame($handler->agencyId(), $refused->decided_agency_id);
        $this->assertNotNull($refused->decided_at);
        $this->assertTrue(Activity::query()->where('event', PortalHistoryEvent::RequestRefused->value)->whereMorphedTo('subject', $refused)->exists());
    }

    public function test_a_refusal_without_a_reason_or_with_a_too_long_reason_is_refused(): void
    {
        $handler = $this->requestHandler();
        $this->actingAs($handler);
        $reservationRequest = $this->pendingRequest($this->customerAccount(), $this->reservableMachine(), '2026-11-10', '2026-11-14');

        Livewire::test(RefuseRequestModal::class, ['reservationRequestId' => $reservationRequest->id])->set('reason', '   ')->call('refuse')->assertHasErrors('reason');
        $this->assertRefused(RefusalReasonRequiredException::class, 'motif', fn () => app(RefuseReservationRequest::class)->handle($reservationRequest, '  ', $handler));
        $this->assertRefused(RefusalReasonRequiredException::class, '500', fn () => app(RefuseReservationRequest::class)->handle($reservationRequest, Str::repeat('a', 501), $handler));

        $this->assertSame(ReservationRequestStatus::Pending, $reservationRequest->fresh()?->status);
    }

    public function test_a_request_already_decided_cannot_be_refused(): void
    {
        $handler = $this->requestHandler();
        $reservationRequest = $this->pendingRequest($this->customerAccount(), $this->reservableMachine(), '2026-11-10', '2026-11-14', ['status' => ReservationRequestStatus::Cancelled, 'decided_at' => now()]);

        $this->assertRefused(IllegalReservationRequestTransitionException::class, 'Annulée', fn () => app(RefuseReservationRequest::class)->handle($reservationRequest, 'Trop tard', $handler));
    }

    public function test_an_employee_without_the_permission_cannot_refuse(): void
    {
        $this->actingAs($this->userWithoutPermission());
        $reservationRequest = $this->pendingRequest($this->customerAccount(), $this->reservableMachine(), '2026-11-10', '2026-11-14');

        Livewire::test(RefuseRequestModal::class, ['reservationRequestId' => $reservationRequest->id])->assertForbidden();
    }
}
