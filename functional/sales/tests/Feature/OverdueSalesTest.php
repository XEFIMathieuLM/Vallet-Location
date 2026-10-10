<?php

namespace Functional\Sales\Tests\Feature;

use Carbon\CarbonImmutable;
use Functional\Sales\Models\Sale;
use Functional\Sales\Queries\OverdueSales;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OverdueSalesTest extends TestCase
{
    use RefreshDatabase;

    public function test_overdue_sales_are_reserved_sales_whose_planned_handover_is_past(): void
    {
        $this->travelTo(CarbonImmutable::parse('2026-10-10 08:00'));
        $today = CarbonImmutable::today();
        $overdue = Sale::factory()->reserved($today->subDay())->create();
        Sale::factory()->reserved($today)->create();
        Sale::factory()->listed()->create();
        Sale::factory()->sold()->create();
        Sale::factory()->cancelled()->create();

        $this->assertSame([$overdue->id], app(OverdueSales::class)->query($today)->pluck('id')->all());
    }
}
