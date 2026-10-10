<?php

namespace Functional\Sales\Tests\Feature;

use Functional\Sales\Enums\SaleStatus;
use Functional\Sales\Models\Sale;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SalesDemoSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_demo_data_covers_every_sale_status(): void
    {
        $this->seed();

        foreach (SaleStatus::cases() as $saleStatus) {
            $this->assertTrue(Sale::query()->where('status', $saleStatus)->exists(), "sale {$saleStatus->value}");
        }
    }
}
