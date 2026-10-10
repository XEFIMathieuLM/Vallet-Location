<?php

namespace Functional\Portal\Tests\Feature;

use Carbon\CarbonImmutable;
use Functional\Booking\Models\Reservation;
use Functional\Fleet\Tests\Concerns\AssertsRefusals;
use Functional\Portal\Actions\CancelReservationRequest;
use Functional\Portal\Actions\ConfirmReservationRequest;
use Functional\Portal\Data\CustomerChoice;
use Functional\Portal\Enums\ReservationRequestStatus;
use Functional\Portal\Exceptions\IllegalReservationRequestTransitionException;
use Functional\Portal\Livewire\Customer\MyRequests;
use Functional\Portal\Livewire\Customer\MyReservations;
use Functional\Portal\Tests\Concerns\BuildsPortalFixtures;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;
use Tests\TestCase;

class CustomerIsolationTest extends TestCase
{
    use AssertsRefusals, BuildsPortalFixtures, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->travelTo(CarbonImmutable::parse('2026-10-10 09:00', 'Europe/Paris'));
        Notification::fake();
    }

    public function test_a_customer_never_sees_nor_cancels_the_requests_or_reservations_of_another_customer(): void
    {
        $otherAccount = $this->attachedCustomerAccount();
        $otherRequest = $this->pendingRequest($otherAccount, $this->reservableMachine(attributes: ['reference' => 'NAC-AUTRUI']), '2026-11-10', '2026-11-12');
        Reservation::factory()->for($otherAccount->customer)->for($this->reservableMachine(attributes: ['reference' => 'NAC-RESA-AUTRUI']))->create();
        $account = $this->attachedCustomerAccount();
        $this->actingAs($account, 'customer');

        Livewire::test(MyRequests::class)->assertDontSee('NAC-AUTRUI');
        Livewire::test(MyReservations::class)->assertDontSee('NAC-RESA-AUTRUI');
        $this->expectException(ModelNotFoundException::class);

        try {
            app(CancelReservationRequest::class)->handle($otherRequest, $account);
        } finally {
            $this->assertSame(ReservationRequestStatus::Pending, $otherRequest->fresh()?->status);
        }
    }

    public function test_a_cancellation_and_a_confirmation_of_the_same_request_never_both_succeed(): void
    {
        $this->seedPermissions();
        $account = $this->customerAccount();
        $machine = $this->reservableMachine();
        $cancelledFirst = $this->pendingRequest($account, $machine, '2026-11-10', '2026-11-12');
        $confirmedFirst = $this->pendingRequest($account, $machine, '2026-11-20', '2026-11-22');
        $handler = $this->requestHandler();

        app(CancelReservationRequest::class)->handle($cancelledFirst, $account);
        $this->assertRefused(IllegalReservationRequestTransitionException::class, 'Annulée', fn () => app(ConfirmReservationRequest::class)->handle($cancelledFirst, $machine, CustomerChoice::create(), $handler));

        app(ConfirmReservationRequest::class)->handle($confirmedFirst, $machine, CustomerChoice::create(), $handler);
        $this->assertRefused(IllegalReservationRequestTransitionException::class, 'Confirmée', fn () => app(CancelReservationRequest::class)->handle($confirmedFirst, $account));

        $this->assertSame(1, Reservation::query()->count());
    }
}
