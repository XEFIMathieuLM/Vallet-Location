<?php

namespace Functional\Sales\Tests\Feature;

use Carbon\CarbonImmutable;
use Functional\Sales\Actions\AcceptOffer;
use Functional\Sales\Enums\OfferStatus;
use Functional\Sales\Enums\SaleStatus;
use Functional\Sales\Exceptions\IllegalSaleTransitionException;
use Functional\Sales\Exceptions\OfferRefusedException;
use Functional\Sales\Models\SaleOffer;
use Functional\Sales\Tests\Concerns\BuildsSalesFixtures;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

class ConcurrentAcceptanceTest extends TestCase
{
    use BuildsSalesFixtures, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->travelTo(CarbonImmutable::parse('2026-11-02 10:00'));
    }

    public function test_once_an_offer_is_accepted_another_acceptance_is_refused(): void
    {
        $sale = $this->listedSale();
        $firstOffer = SaleOffer::factory()->create(['sale_id' => $sale->id]);
        $secondOffer = SaleOffer::factory()->create(['sale_id' => $sale->id]);
        app(AcceptOffer::class)->handle($this->employee(), $firstOffer, CarbonImmutable::parse('2026-11-20'));

        $this->assertRefused(IllegalSaleTransitionException::class, 'Réservée', fn () => app(AcceptOffer::class)->handle($this->employee(), $secondOffer, CarbonImmutable::parse('2026-11-21')));
        $this->assertSame(1, SaleOffer::query()->where('status', OfferStatus::Accepted)->count());
    }

    public function test_when_two_acceptances_reach_the_database_together_only_one_is_kept(): void
    {
        $author = $this->employee();
        $sale = $this->listedSale();
        $offer = SaleOffer::factory()->create(['sale_id' => $sale->id]);
        $competingOffer = SaleOffer::factory()->create(['sale_id' => $sale->id]);
        $hasCompeted = false;
        SaleOffer::updating(function () use ($competingOffer, $author, &$hasCompeted): void {
            if ($hasCompeted) {
                return;
            }

            $hasCompeted = true;
            DB::table('sale_offers')->where('id', $competingOffer->id)->update(['status' => 'accepted', 'decided_by' => $author->getKey(), 'decided_at' => now()]);
        });

        $this->assertRefused(OfferRefusedException::class, 'même moment', fn () => app(AcceptOffer::class)->handle($author, $offer, CarbonImmutable::parse('2026-11-20')));
        $this->assertSame(SaleStatus::Listed, $sale->fresh()?->status);
        $this->assertSame(OfferStatus::Pending, $competingOffer->fresh()?->status);
    }

    public function test_the_acceptance_locks_the_machine_like_a_new_rental_does(): void
    {
        $sale = $this->listedSale();
        $offer = SaleOffer::factory()->create(['sale_id' => $sale->id]);
        DB::enableQueryLog();

        app(AcceptOffer::class)->handle($this->employee(), $offer, CarbonImmutable::parse('2026-11-20'));

        $machineLocks = collect(DB::getQueryLog())->filter(fn (array $query): bool => Str::contains($query['query'], 'from "machines"') && Str::contains($query['query'], 'for update'));
        $this->assertNotEmpty($machineLocks);
    }
}
