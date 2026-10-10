<?php

namespace Functional\Sales\Tests\Feature;

use Functional\Billing\Money\Money;
use Functional\Sales\Models\Sale;
use Functional\Sales\Models\SaleOffer;
use Functional\Sales\Tests\Concerns\BuildsSalesFixtures;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;
use Throwable;

class SaleConstraintsTest extends TestCase
{
    use BuildsSalesFixtures, RefreshDatabase;

    public function test_the_database_refuses_a_non_positive_asking_price(): void
    {
        $sale = Sale::factory()->listed()->create();

        $this->assertRefusedByTheDatabase(fn () => DB::table('sales')->whereKey($sale->id)->update(['asking_price_cents' => 0]));
    }

    public function test_the_database_refuses_a_second_open_sale_of_the_same_machine(): void
    {
        $sale = Sale::factory()->listed()->create();

        $this->assertRefusedByTheDatabase(fn () => Sale::factory()->listed()->create(['machine_id' => $sale->machine_id]));
    }

    public function test_the_database_refuses_any_new_sale_of_a_sold_machine(): void
    {
        $sale = Sale::factory()->sold()->create();

        $this->assertRefusedByTheDatabase(fn () => Sale::factory()->listed()->create(['machine_id' => $sale->machine_id]));
    }

    public function test_a_cancelled_sale_does_not_prevent_a_new_sale(): void
    {
        $cancelledSale = Sale::factory()->cancelled()->create();

        Sale::factory()->listed()->create(['machine_id' => $cancelledSale->machine_id]);

        $this->assertSame(2, Sale::query()->where('machine_id', $cancelledSale->machine_id)->count());
    }

    public function test_the_database_refuses_a_second_accepted_offer_on_a_sale(): void
    {
        $sale = Sale::factory()->reserved()->create();

        $this->assertRefusedByTheDatabase(fn () => SaleOffer::factory()->accepted()->create(['sale_id' => $sale->id]));
    }

    public function test_the_database_refuses_a_reserved_sale_without_buyer_price_or_handover_date(): void
    {
        $sale = Sale::factory()->listed()->create();

        $this->assertRefusedByTheDatabase(fn () => DB::table('sales')->whereKey($sale->id)->update(['status' => 'reserved']));
    }

    public function test_the_database_refuses_a_sold_sale_without_handover(): void
    {
        $sale = Sale::factory()->reserved()->create();

        $this->assertRefusedByTheDatabase(fn () => DB::table('sales')->whereKey($sale->id)->update(['status' => 'sold']));
    }

    public function test_the_database_refuses_a_cancelled_sale_without_reason(): void
    {
        $sale = Sale::factory()->listed()->create();

        $this->assertRefusedByTheDatabase(fn () => DB::table('sales')->whereKey($sale->id)->update(['status' => 'cancelled']));
    }

    public function test_the_database_refuses_a_non_positive_offer_and_a_decided_offer_without_author(): void
    {
        $sale = Sale::factory()->listed()->create();

        $this->assertRefusedByTheDatabase(fn () => SaleOffer::factory()->create(['sale_id' => $sale->id, 'amount' => Money::fromStored(0)]));
        $this->assertRefusedByTheDatabase(fn () => SaleOffer::factory()->create(['sale_id' => $sale->id, 'status' => 'rejected']));
        $this->assertSame(0, SaleOffer::query()->count());
    }

    private function assertRefusedByTheDatabase(callable $write): void
    {
        $refusal = rescue(fn () => DB::transaction($write), fn (Throwable $exception): Throwable => $exception, report: false);

        $this->assertInstanceOf(QueryException::class, $refusal);
    }
}
