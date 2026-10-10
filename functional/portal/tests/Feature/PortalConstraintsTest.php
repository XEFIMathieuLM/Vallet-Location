<?php

namespace Functional\Portal\Tests\Feature;

use Functional\Booking\Models\Reservation;
use Functional\Fleet\Models\Machine;
use Functional\Fleet\Models\MachineCategory;
use Functional\Portal\Enums\ReservationRequestStatus;
use Functional\Portal\Models\CategoryIndicativePrice;
use Functional\Portal\Models\CustomerAccount;
use Functional\Portal\Models\ReservationRequest;
use Functional\Portal\Tests\Concerns\BuildsPortalFixtures;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class PortalConstraintsTest extends TestCase
{
    use BuildsPortalFixtures, RefreshDatabase;

    public function test_the_database_refuses_two_overlapping_pending_requests_of_the_same_account_on_the_same_machine(): void
    {
        $account = $this->customerAccount();
        $machine = Machine::factory()->create();
        $this->pendingRequest($account, $machine, '2030-11-10', '2030-11-14');

        $refusal = $this->refusalOf(fn () => $this->pendingRequest($account, $machine, '2030-11-14', '2030-11-16'));

        $this->assertSame('23P01', $refusal->getCode());
        $this->assertSame(1, ReservationRequest::query()->count());
    }

    public function test_the_database_accepts_an_overlap_with_a_cancelled_request_or_another_account(): void
    {
        $account = $this->customerAccount();
        $machine = Machine::factory()->create();
        $this->pendingRequest($account, $machine, '2030-11-10', '2030-11-14', ['status' => ReservationRequestStatus::Cancelled, 'decided_at' => now()]);

        $this->pendingRequest($account, $machine, '2030-11-10', '2030-11-14');
        $this->pendingRequest($this->customerAccount(), $machine, '2030-11-10', '2030-11-14');

        $this->assertSame(3, ReservationRequest::query()->count());
    }

    public function test_the_database_refuses_two_requests_pointing_to_the_same_reservation(): void
    {
        $reservation = Reservation::factory()->create();
        ReservationRequest::factory()->confirmed($reservation)->create();

        $refusal = $this->refusalOf(fn () => ReservationRequest::factory()->confirmed($reservation)->create());

        $this->assertSame('23505', $refusal->getCode());
    }

    public function test_the_database_refuses_a_confirmed_request_without_a_reservation(): void
    {
        $refusal = $this->refusalOf(fn () => ReservationRequest::factory()->create(['status' => ReservationRequestStatus::Confirmed, 'decided_at' => now()]));

        $this->assertSame('23514', $refusal->getCode());
    }

    public function test_the_database_refuses_a_refused_request_without_a_reason(): void
    {
        $refusal = $this->refusalOf(fn () => ReservationRequest::factory()->create(['status' => ReservationRequestStatus::Refused, 'decided_at' => now()]));

        $this->assertSame('23514', $refusal->getCode());
    }

    public function test_the_database_refuses_an_end_date_before_the_start_date(): void
    {
        $refusal = $this->refusalOf(fn () => $this->pendingRequest($this->customerAccount(), Machine::factory()->create(), '2030-11-14', '2030-11-10'));

        $this->assertSame('23514', $refusal->getCode());
    }

    public function test_the_database_refuses_two_accounts_with_the_same_email(): void
    {
        $this->customerAccount(['email' => 'chantier@exemple.fr']);

        $refusal = $this->refusalOf(fn () => $this->customerAccount(['email' => 'chantier@exemple.fr']));

        $this->assertSame('23505', $refusal->getCode());
        $this->assertSame(1, CustomerAccount::query()->count());
    }

    public function test_the_database_refuses_a_price_that_is_not_strictly_positive(): void
    {
        $refusal = $this->refusalOf(fn () => CategoryIndicativePrice::factory()->for(MachineCategory::factory(), 'category')->create(['daily_price_cents' => 0]));

        $this->assertSame('23514', $refusal->getCode());
    }

    private function refusalOf(callable $insertion): QueryException
    {
        $refusal = rescue(fn () => DB::transaction($insertion), fn ($exception) => $exception, report: false);

        $this->assertInstanceOf(QueryException::class, $refusal);

        return $refusal;
    }
}
