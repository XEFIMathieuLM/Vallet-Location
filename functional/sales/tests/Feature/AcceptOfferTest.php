<?php

namespace Functional\Sales\Tests\Feature;

use Carbon\CarbonImmutable;
use Functional\Billing\Money\Money;
use Functional\Booking\Models\Customer;
use Functional\Sales\Actions\AcceptOffer;
use Functional\Sales\Enums\OfferStatus;
use Functional\Sales\Enums\SaleStatus;
use Functional\Sales\Exceptions\HandoverConflictsWithReservationException;
use Functional\Sales\Exceptions\InvalidHandoverDateException;
use Functional\Sales\Models\Sale;
use Functional\Sales\Models\SaleOffer;
use Functional\Sales\Tests\Concerns\BuildsSalesFixtures;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Activitylog\Models\Activity;
use Tests\TestCase;

class AcceptOfferTest extends TestCase
{
    use BuildsSalesFixtures, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->travelTo(CarbonImmutable::parse('2026-11-02 10:00'));
    }

    public function test_accepting_an_offer_reserves_the_sale_for_its_buyer_and_rejects_the_other_offers(): void
    {
        $sale = $this->listedSale();
        $acceptedOffer = $this->pendingOffer($sale, 16500);
        $otherOffer = $this->pendingOffer($sale, 15000);

        $reservedSale = $this->accept($acceptedOffer, '2026-11-20');

        $this->assertSame(SaleStatus::Reserved, $reservedSale->status);
        $this->assertSame($acceptedOffer->customer_id, $reservedSale->buyer_id);
        $this->assertTrue(Money::fromStored(1650000)->equals($reservedSale->final_price));
        $this->assertSame('2026-11-20', $reservedSale->planned_handover_date?->toDateString());
        $this->assertSame(OfferStatus::Accepted, $acceptedOffer->fresh()?->status);
        $this->assertSame(OfferStatus::Rejected, $otherOffer->fresh()?->status);
    }

    public function test_an_offer_cannot_be_accepted_while_a_rental_ends_on_or_after_the_handover_date(): void
    {
        $sale = $this->listedSale();
        $reservation = $this->confirmedReservation($sale->machine, '2026-11-18', '2026-11-25');
        $reservation->customer->update(['name' => 'BTP Durand']);

        $this->assertRefused(HandoverConflictsWithReservationException::class, 'BTP Durand', fn () => $this->accept($this->pendingOffer($sale, 16500), '2026-11-20'));
        $this->assertRefused(HandoverConflictsWithReservationException::class, '18/11/2026', fn () => $this->accept($this->pendingOffer($sale, 16500), '2026-11-25'));
        $this->assertSame(SaleStatus::Listed, $sale->fresh()?->status);
    }

    public function test_a_rental_ending_the_day_before_the_handover_is_compatible(): void
    {
        $sale = $this->listedSale();
        $this->confirmedReservation($sale->machine, '2026-11-10', '2026-11-19');

        $this->assertSame(SaleStatus::Reserved, $this->accept($this->pendingOffer($sale, 16500), '2026-11-20')->status);
    }

    public function test_an_offer_far_below_the_asking_price_is_accepted_and_its_author_is_traced(): void
    {
        $author = $this->employee();
        $sale = $this->listedSale(askingPriceInEuros: 18000);

        app(AcceptOffer::class)->handle($author, $this->pendingOffer($sale, 12000), CarbonImmutable::parse('2026-11-20'));

        $activity = Activity::query()->where('log_name', 'sales')->where('event', 'offer_accepted')->sole();
        $this->assertSame($author->getKey(), $activity->causer_id);
        $this->assertSame('12000,00', $activity->properties['amount']);
    }

    public function test_a_handover_date_in_the_past_is_refused(): void
    {
        $this->assertRefused(InvalidHandoverDateException::class, 'passé', fn () => $this->accept($this->pendingOffer($this->listedSale(), 16500), '2026-11-01'));
    }

    private function pendingOffer(Sale $sale, int $amountInEuros): SaleOffer
    {
        return SaleOffer::factory()->create([
            'sale_id' => $sale->id,
            'customer_id' => Customer::factory(),
            'amount' => Money::fromStored($amountInEuros * 100),
        ]);
    }

    private function accept(SaleOffer $offer, string $plannedHandoverDate): Sale
    {
        return app(AcceptOffer::class)->handle($this->employee(), $offer, CarbonImmutable::parse($plannedHandoverDate));
    }
}
