<?php

namespace Functional\Portal\Tests\Feature\Staff;

use Carbon\CarbonImmutable;
use Functional\Booking\Models\Customer;
use Functional\Booking\Models\Reservation;
use Functional\Fleet\Tests\Concerns\AssertsRefusals;
use Functional\Portal\Actions\AttachCustomerAccount;
use Functional\Portal\Actions\ConfirmReservationRequest;
use Functional\Portal\Data\CustomerChoice;
use Functional\Portal\Exceptions\CustomerAccountAlreadyAttachedException;
use Functional\Portal\Exceptions\IllegalReservationRequestTransitionException;
use Functional\Portal\Tests\Concerns\BuildsPortalFixtures;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class ConfirmationConcurrencyTest extends TestCase
{
    use AssertsRefusals, BuildsPortalFixtures, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->travelTo(CarbonImmutable::parse('2026-10-10 09:00', 'Europe/Paris'));
        Notification::fake();
        $this->seedPermissions();
    }

    public function test_a_request_confirmed_twice_creates_a_single_reservation(): void
    {
        $machine = $this->reservableMachine();
        $reservationRequest = $this->pendingRequest($this->customerAccount(), $machine, '2026-11-10', '2026-11-14');
        $staleCopy = $reservationRequest->replicate();
        $staleCopy->id = $reservationRequest->id;
        $staleCopy->exists = true;

        app(ConfirmReservationRequest::class)->handle($reservationRequest, $machine, CustomerChoice::create(), $this->requestHandler());

        $this->assertRefused(IllegalReservationRequestTransitionException::class, 'Confirmée', fn () => app(ConfirmReservationRequest::class)->handle($staleCopy, $machine, CustomerChoice::create(), $this->requestHandler()));
        $this->assertSame(1, Reservation::query()->count());
        $this->assertSame(1, Customer::query()->count());
    }

    public function test_two_requests_of_an_unattached_account_never_create_two_customer_records(): void
    {
        $account = $this->customerAccount();
        $first = $this->pendingRequest($account, $this->reservableMachine(), '2026-11-10', '2026-11-14');
        $second = $this->pendingRequest($account, $this->reservableMachine(), '2026-11-20', '2026-11-21');

        app(ConfirmReservationRequest::class)->handle($first, $first->machine, CustomerChoice::create(), $this->requestHandler());
        app(ConfirmReservationRequest::class)->handle($second, $second->machine, CustomerChoice::create(), $this->requestHandler());

        $customer = Customer::query()->sole();
        $this->assertSame([$customer->id, $customer->id], Reservation::query()->orderBy('id')->pluck('customer_id')->all());
        $this->assertSame($customer->id, $account->fresh()?->customer_id);
    }

    public function test_an_attached_account_cannot_be_attached_to_another_record(): void
    {
        $account = $this->attachedCustomerAccount();
        $otherCustomer = Customer::factory()->create();

        $this->assertRefused(CustomerAccountAlreadyAttachedException::class, 'déjà rattaché', fn () => app(AttachCustomerAccount::class)->handle($account, $otherCustomer, $this->requestHandler()));
        $this->assertNotSame($otherCustomer->id, $account->fresh()?->customer_id);
    }
}
