<?php

namespace Functional\Sales\Tests\Feature;

use Carbon\CarbonImmutable;
use Functional\Billing\Enums\BillableLineType;
use Functional\Billing\Enums\TransmissionStatus;
use Functional\Billing\Models\CustomerBillingAccount;
use Functional\Billing\Models\Transmission;
use Functional\Booking\Actions\CreateReservation;
use Functional\Booking\Enums\ReservationStatus;
use Functional\Booking\Exceptions\MachineNotReservableException;
use Functional\Booking\Models\Customer;
use Functional\Booking\Queries\AvailableMachinesQuery;
use Functional\Fleet\Enums\MachineStatus;
use Functional\Fleet\Exceptions\MachineRetirementRefusedException;
use Functional\Fleet\Extensions\MachineRetirementGuards;
use Functional\Fleet\Models\Machine;
use Functional\Fleet\Tests\Doubles\RefusingRetirementGuard;
use Functional\Sales\Actions\HandOverSale;
use Functional\Sales\Enums\SaleStatus;
use Functional\Sales\Exceptions\SaleHandoverRefusedException;
use Functional\Sales\Livewire\SaleDetail;
use Functional\Sales\Models\Sale;
use Functional\Sales\Tests\Concerns\BuildsSalesFixtures;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Livewire\Livewire;
use Tests\TestCase;

class HandOverSaleTest extends TestCase
{
    use BuildsSalesFixtures, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->travelTo(CarbonImmutable::parse('2026-11-20 10:00'));
    }

    public function test_the_handover_concludes_the_sale_and_retires_the_machine(): void
    {
        Bus::fake();
        $author = $this->employee();
        $sale = $this->reservedSale('2026-11-20');

        $soldSale = app(HandOverSale::class)->handle($author, $sale);

        $this->assertSame(SaleStatus::Sold, $soldSale->status);
        $this->assertSame('2026-11-20', $soldSale->handed_over_on?->toDateString());
        $this->assertSame($author->getKey(), $soldSale->handed_over_by);
        $this->assertSame(MachineStatus::Retired, $sale->machine->fresh()?->status);
    }

    public function test_the_handover_is_refused_while_the_machine_is_rented_out(): void
    {
        $sale = $this->reservedSale('2026-11-25');
        $this->confirmedReservation($sale->machine, '2026-11-18', '2026-11-22', ReservationStatus::InProgress);

        $this->assertRefused(SaleHandoverRefusedException::class, 'louée', fn () => app(HandOverSale::class)->handle($this->employee(), $sale));
        $this->assertSame(SaleStatus::Reserved, $sale->fresh()?->status);
    }

    public function test_the_handover_is_refused_and_lists_the_confirmed_rentals_to_cancel_or_move(): void
    {
        $sale = $this->reservedSale('2026-11-25');
        $this->confirmedReservation($sale->machine, '2026-11-21', '2026-11-23');

        $this->assertRefused(SaleHandoverRefusedException::class, '21/11/2026', fn () => app(HandOverSale::class)->handle($this->employee(), $sale));
        $this->assertSame(MachineStatus::Available, $sale->machine->fresh()?->status);
    }

    public function test_a_sold_machine_disappears_from_the_search_and_cannot_be_reserved(): void
    {
        Bus::fake();
        $sale = $this->reservedSale('2026-11-20');
        app(HandOverSale::class)->handle($this->employee(), $sale);

        $this->assertFalse(app(AvailableMachinesQuery::class)->get(CarbonImmutable::parse('2026-12-01'), CarbonImmutable::parse('2026-12-03'))->contains($sale->machine));
        $this->assertRefused(MachineNotReservableException::class, '', fn () => app(CreateReservation::class)->handle(
            $this->employee(), $sale->machine->fresh() ?? $sale->machine, Customer::factory()->create(), CarbonImmutable::parse('2026-12-01'), CarbonImmutable::parse('2026-12-03'),
        ));
    }

    public function test_the_handover_creates_one_transmission_carrying_buyer_machine_and_price(): void
    {
        $sale = $this->reservedSale('2026-11-20');
        CustomerBillingAccount::factory()->create(['customer_id' => $sale->buyer_id, 'external_ref' => 'CLI-90']);

        app(HandOverSale::class)->handle($this->employee(), $sale);

        $transmission = Transmission::query()->sole();
        $this->assertSame(BillableLineType::UsedMachineSale, $transmission->source_type);
        $this->assertSame($sale->id, $transmission->source_id);
        $this->assertSame(TransmissionStatus::Sent, $transmission->status);
        $line = $this->fakeGateway()->received()[$transmission->uuid];
        $this->assertSame('used_machine_sale', $line['type']);
        $this->assertSame('CLI-90', $line['customer_ref']);
        $this->assertSame("SALE-{$sale->id}", $line['source_ref']);
        $this->assertSame($sale->machine->reference, $line['machine_reference']);
        $this->assertSame('2026-11-20', $line['sale_date']);
        $this->assertSame($sale->final_price?->minorUnits, $line['amount_excl_tax_cents']);
    }

    public function test_the_handover_may_happen_before_or_after_the_planned_date(): void
    {
        Bus::fake();
        $early = $this->reservedSale('2026-11-25', $this->machineForSale('NAC-0001'));
        $late = Sale::factory()->reserved(CarbonImmutable::parse('2026-11-20'))->create(['machine_id' => $this->machineForSale('NAC-0002')->id]);
        $this->travelTo(CarbonImmutable::parse('2026-11-22 10:00'));

        $this->assertSame(SaleStatus::Sold, app(HandOverSale::class)->handle($this->employee(), $early)->status);
        $this->assertSame(SaleStatus::Sold, app(HandOverSale::class)->handle($this->employee(), $late)->status);
    }

    public function test_an_already_retired_machine_stays_retired_and_a_refused_retirement_cancels_everything(): void
    {
        Bus::fake();
        $retiredSale = $this->reservedSale('2026-11-20', Machine::factory()->withStatus(MachineStatus::Retired)->create());
        $this->assertSame(SaleStatus::Sold, app(HandOverSale::class)->handle($this->employee(), $retiredSale)->status);
        $this->assertSame(MachineStatus::Retired, $retiredSale->machine->fresh()?->status);

        app(MachineRetirementGuards::class)->register(RefusingRetirementGuard::class);
        $sale = $this->reservedSale('2026-11-20', $this->machineForSale('NAC-0099'));
        $this->assertRefused(MachineRetirementRefusedException::class, 'NAC-0099', fn () => app(HandOverSale::class)->handle($this->employee(), $sale));
        $this->assertSame(SaleStatus::Reserved, $sale->fresh()?->status);
        $this->assertSame(0, Transmission::query()->where('source_id', $sale->id)->count());
    }

    public function test_the_sale_screen_records_the_handover_and_shows_the_frozen_sale(): void
    {
        Bus::fake();
        $sale = $this->reservedSale('2026-11-20');

        Livewire::actingAs($this->employee())->test(SaleDetail::class, ['sale' => $sale])
            ->call('handOver')
            ->assertHasNoErrors()
            ->assertSee('avoir');

        $this->assertSame(SaleStatus::Sold, $sale->fresh()?->status);
    }
}
