<?php

namespace Functional\Billing\Tests\Feature;

use Functional\Billing\Actions\BillDamage;
use Functional\Billing\Actions\CreateBillingExport;
use Functional\Billing\Actions\RetryTransmission;
use Functional\Billing\Actions\SetCustomerBillingRef;
use Functional\Billing\Actions\WaiveDamage;
use Functional\Billing\Enums\FakeGatewayMode;
use Functional\Billing\Models\Transmission;
use Functional\Billing\Money\Money;
use Functional\Billing\Tests\Concerns\BuildsBillingFixtures;
use Functional\Billing\Tests\Concerns\RecordsReservationLifecycle;
use Functional\Booking\Models\Reservation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Spatie\Activitylog\Models\Activity;
use Tests\TestCase;

class BillingHistoryTest extends TestCase
{
    use BuildsBillingFixtures, RecordsReservationLifecycle, RefreshDatabase;

    public function test_the_reservation_history_traces_every_billing_step_with_author_and_date(): void
    {
        Storage::fake('billing-exports');
        $employee = $this->employee();
        $this->actingAs($employee);
        $reservation = $this->inProgressReservation('2026-11-10 08:00:00', hasBillingAccount: false);
        $this->recordReturn($reservation, '2026-11-14 17:00:00');
        $transmission = Transmission::query()->where('reservation_id', $reservation->id)->sole();
        app(SetCustomerBillingRef::class)->handle($reservation->customer, 'CLI-1');
        app(RetryTransmission::class)->handle($transmission);
        app(BillDamage::class)->handle($this->unresolvedDamage($reservation, 'Gauche'), Money::fromStored(45000), 'remplacement capot', $employee);
        app(WaiveDamage::class)->handle($this->unresolvedDamage($reservation, 'Droite'), 'usure normale', $employee);
        $this->fakeGateway()->switchTo(FakeGatewayMode::Unreachable);
        app(BillDamage::class)->handle($this->unresolvedDamage($reservation, 'Avant'), Money::fromStored(12000), 'rétroviseur', $employee);
        app(CreateBillingExport::class)->handle($employee);

        $this->assertSame(
            ['period_created', 'failed', 'retried', 'sent', 'damage_billed', 'sent', 'damage_waived', 'damage_billed', 'unreachable', 'exported'],
            $this->billingHistoryOf($reservation)->pluck('event')->all(),
        );
        $this->assertSame($employee->id, $this->billingHistoryOf($reservation)->firstWhere('event', 'retried')?->causer_id);
        $this->assertSame($employee->id, $this->billingHistoryOf($reservation)->firstWhere('event', 'damage_waived')?->causer_id);
        $this->assertStringContainsString('usure normale', (string) $this->billingHistoryOf($reservation)->firstWhere('event', 'damage_waived')?->description);
        $this->assertTrue($this->billingHistoryOf($reservation)->every(fn (Activity $activity): bool => $activity->created_at !== null));
    }

    public function test_steps_run_without_a_user_are_traced_as_system(): void
    {
        $reservation = $this->closedReservation('2026-11-10 08:00:00', '2026-11-14 17:00:00');

        $this->artisan('billing:reconcile')->assertSuccessful();

        $this->assertSame([null, null], $this->billingHistoryOf($reservation)->pluck('causer_id')->all());
        $this->assertNull($this->billingHistoryOf($reservation)->first()?->causer);
    }

    /**
     * @return Collection<int, Activity>
     */
    private function billingHistoryOf(Reservation $reservation): Collection
    {
        return Activity::query()
            ->where('log_name', 'billing')
            ->where('subject_type', $reservation->getMorphClass())
            ->where('subject_id', $reservation->id)
            ->orderBy('id')
            ->get()
            ->toBase();
    }
}
