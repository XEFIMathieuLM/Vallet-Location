<?php

namespace Functional\Sales\Tests\Feature;

use Carbon\CarbonImmutable;
use Functional\Billing\Money\Money;
use Functional\Booking\Actions\CreateReservation;
use Functional\Booking\Enums\ReservationStatus;
use Functional\Booking\Models\Customer;
use Functional\Sales\Actions\RecordOffer;
use Functional\Sales\Actions\ReleaseSaleReservation;
use Functional\Sales\Enums\OfferStatus;
use Functional\Sales\Enums\SaleStatus;
use Functional\Sales\Exceptions\ReasonRequiredException;
use Functional\Sales\Tests\Concerns\BuildsSalesFixtures;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Activitylog\Models\Activity;
use Tests\TestCase;

class ReleaseSaleReservationTest extends TestCase
{
    use BuildsSalesFixtures, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->travelTo(CarbonImmutable::parse('2026-11-02 10:00'));
    }

    public function test_releasing_the_reservation_puts_the_sale_back_on_sale_and_frees_the_rentals(): void
    {
        $sale = $this->reservedSale('2026-11-20');
        $acceptedOffer = $sale->acceptedOffer;

        $releasedSale = app(ReleaseSaleReservation::class)->handle($this->employee(), $sale, 'Financement refusé');

        $this->assertSame(SaleStatus::Listed, $releasedSale->status);
        $this->assertNull($releasedSale->buyer_id);
        $this->assertNull($releasedSale->accepted_offer_id);
        $this->assertNull($releasedSale->final_price);
        $this->assertNull($releasedSale->planned_handover_date);
        $this->assertSame(OfferStatus::Withdrawn, $acceptedOffer?->fresh()?->status);
        $this->assertSame('Financement refusé', Activity::query()->where('event', 'reservation_released')->sole()->properties['reason']);

        $reservation = app(CreateReservation::class)->handle($this->employee(), $sale->machine, Customer::factory()->create(), CarbonImmutable::parse('2026-11-18'), CarbonImmutable::parse('2026-11-22'));
        $this->assertSame(ReservationStatus::Confirmed, $reservation->status);
        $newOffer = app(RecordOffer::class)->handle($this->employee(), $releasedSale, Customer::factory()->create(), Money::fromInput('15500'), CarbonImmutable::today());
        $this->assertSame(OfferStatus::Pending, $newOffer->status);
    }

    public function test_the_reservation_cannot_be_released_without_a_reason(): void
    {
        $sale = $this->reservedSale('2026-11-20');

        $this->assertRefused(ReasonRequiredException::class, 'motif', fn () => app(ReleaseSaleReservation::class)->handle($this->employee(), $sale, '  '));
        $this->assertSame(SaleStatus::Reserved, $sale->fresh()?->status);
    }
}
