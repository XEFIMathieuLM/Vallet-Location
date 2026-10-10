<?php

namespace Functional\Sales\Tests\Feature;

use Carbon\CarbonImmutable;
use Functional\Billing\Money\Money;
use Functional\Booking\Models\Customer;
use Functional\Sales\Actions\RecordOffer;
use Functional\Sales\Actions\UpdateSaleListing;
use Functional\Sales\Data\SaleListing;
use Functional\Sales\Exceptions\OfferRefusedException;
use Functional\Sales\Exceptions\SaleListingLockedException;
use Functional\Sales\Models\Sale;
use Functional\Sales\Tests\Concerns\BuildsSalesFixtures;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SoldSaleIsFrozenTest extends TestCase
{
    use BuildsSalesFixtures, RefreshDatabase;

    public function test_the_price_and_description_of_a_sold_sale_can_no_longer_change(): void
    {
        $sale = Sale::factory()->sold()->create();

        $this->assertRefused(SaleListingLockedException::class, 'avoir', fn () => app(UpdateSaleListing::class)->handle($this->employee(), $sale, new SaleListing(Money::fromInput('1'), null, null, 'Autre', null)));
    }

    public function test_a_sold_sale_receives_no_more_offers(): void
    {
        $sale = Sale::factory()->sold()->create();

        $this->assertRefused(OfferRefusedException::class, 'Vendue', fn () => app(RecordOffer::class)->handle($this->employee(), $sale, Customer::factory()->create(), Money::fromInput('1000'), CarbonImmutable::today()));
    }
}
