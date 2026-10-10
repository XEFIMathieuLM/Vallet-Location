<?php

namespace Functional\Accounts\Tests\Feature;

use Database\Seeders\DatabaseSeeder;
use Functional\Accounts\Models\KeyAccount;
use Functional\Accounts\Models\ReservationPurchaseOrder;
use Functional\Accounts\Queries\MissingPurchaseOrders;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AccountsDemoSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_demo_data_shows_key_accounts_with_and_without_purchase_orders(): void
    {
        $this->seed(DatabaseSeeder::class);

        $this->assertSame(2, KeyAccount::query()->count());
        $this->assertSame(1, ReservationPurchaseOrder::query()->count());
        $this->assertSame(2, app(MissingPurchaseOrders::class)->query()->count());
    }
}
