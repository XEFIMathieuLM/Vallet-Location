<?php

namespace Functional\Sales\Tests\Feature;

use Carbon\CarbonImmutable;
use Functional\Billing\Money\Money;
use Functional\Booking\Actions\CreateReservation;
use Functional\Booking\Enums\ReservationStatus;
use Functional\Booking\Models\Customer;
use Functional\Fleet\Enums\MachineStatus;
use Functional\Sales\Actions\CancelSale;
use Functional\Sales\Actions\ListMachineForSale;
use Functional\Sales\Data\SaleListing;
use Functional\Sales\Enums\OfferStatus;
use Functional\Sales\Enums\SaleStatus;
use Functional\Sales\Exceptions\ReasonRequiredException;
use Functional\Sales\Exceptions\SaleCancellationRefusedException;
use Functional\Sales\Models\Sale;
use Functional\Sales\Models\SaleOffer;
use Functional\Sales\Tests\Concerns\BuildsSalesFixtures;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CancelSaleTest extends TestCase
{
    use BuildsSalesFixtures, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->travelTo(CarbonImmutable::parse('2026-11-02 10:00'));
    }

    public function test_cancelling_a_reserved_sale_keeps_the_machine_and_frees_the_rentals(): void
    {
        $sale = $this->reservedSale('2026-11-20');
        $acceptedOffer = $sale->acceptedOffer;
        $pendingOffer = SaleOffer::factory()->create(['sale_id' => $sale->id]);

        $cancelledSale = app(CancelSale::class)->handle($this->employee(), $sale, 'Acheteur désisté');

        $this->assertSame(SaleStatus::Cancelled, $cancelledSale->status);
        $this->assertSame('Acheteur désisté', $cancelledSale->cancellation_reason);
        $this->assertSame(OfferStatus::Withdrawn, $acceptedOffer?->fresh()?->status);
        $this->assertSame(OfferStatus::Rejected, $pendingOffer->fresh()?->status);
        $this->assertSame(MachineStatus::Available, $sale->machine->fresh()?->status);
        $reservation = app(CreateReservation::class)->handle($this->employee(), $sale->machine, Customer::factory()->create(), CarbonImmutable::parse('2026-11-18'), CarbonImmutable::parse('2026-11-22'));
        $this->assertSame(ReservationStatus::Confirmed, $reservation->status);
    }

    public function test_a_sale_cannot_be_cancelled_without_a_reason(): void
    {
        $sale = $this->listedSale();

        $this->assertRefused(ReasonRequiredException::class, 'motif', fn () => app(CancelSale::class)->handle($this->employee(), $sale, ''));
        $this->assertSame(SaleStatus::Listed, $sale->fresh()?->status);
    }

    public function test_a_sold_sale_cannot_be_cancelled(): void
    {
        $sale = Sale::factory()->sold()->create();

        $this->assertRefused(SaleCancellationRefusedException::class, 'avoir', fn () => app(CancelSale::class)->handle($this->employee(), $sale, 'Erreur'));
    }

    public function test_a_cancelled_machine_can_be_listed_again_and_the_old_sale_is_kept(): void
    {
        $sale = $this->listedSale();
        app(CancelSale::class)->handle($this->employee(), $sale, 'Machine conservée');

        $newSale = app(ListMachineForSale::class)->handle($this->employee(), $sale->machine, new SaleListing(Money::fromInput('16000'), null, null, 'Bon état', null));

        $this->assertSame(SaleStatus::Listed, $newSale->status);
        $this->assertSame(2, Sale::query()->whereBelongsTo($sale->machine)->count());
    }
}
