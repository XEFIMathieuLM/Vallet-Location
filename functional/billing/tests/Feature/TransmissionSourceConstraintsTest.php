<?php

namespace Functional\Billing\Tests\Feature;

use Functional\Billing\Models\BillablePeriod;
use Functional\Billing\Models\Transmission;
use Functional\Booking\Models\Reservation;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Tests\TestCase;
use Throwable;

class TransmissionSourceConstraintsTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_database_refuses_a_transmission_without_any_source(): void
    {
        $this->assertInsertRefused(['reservation_id' => Reservation::factory()->create()->id]);
    }

    public function test_the_database_refuses_a_transmission_with_two_sources(): void
    {
        $billablePeriod = BillablePeriod::factory()->create();

        $this->assertInsertRefused(['billable_period_id' => $billablePeriod->id, 'reservation_id' => $billablePeriod->reservation_id, 'source_type' => 'used_machine_sale', 'source_id' => 1]);
    }

    public function test_the_database_refuses_a_source_type_without_source_id(): void
    {
        $billablePeriod = BillablePeriod::factory()->create();

        $this->assertInsertRefused(['billable_period_id' => $billablePeriod->id, 'reservation_id' => $billablePeriod->reservation_id, 'source_type' => 'used_machine_sale']);
    }

    public function test_the_database_refuses_a_rental_period_without_reservation(): void
    {
        $this->assertInsertRefused(['billable_period_id' => BillablePeriod::factory()->create()->id]);
    }

    public function test_the_database_refuses_a_second_transmission_of_the_same_source(): void
    {
        $this->insert(['source_type' => 'used_machine_sale', 'source_id' => 42]);

        $this->assertInsertRefused(['source_type' => 'used_machine_sale', 'source_id' => 42]);
    }

    public function test_rolling_back_the_source_migration_restores_the_rental_only_schema_and_keeps_rental_transmissions(): void
    {
        $transmission = Transmission::factory()->create();

        Artisan::call('migrate:rollback', ['--path' => 'functional/billing/database/migrations/2026_10_10_000065_add_source_to_transmissions_table.php']);

        $this->assertFalse(Schema::hasColumn('transmissions', 'source_type'));
        $this->assertFalse(Schema::hasColumn('transmissions', 'source_id'));
        $this->assertTrue(Transmission::query()->whereKey($transmission->id)->exists());

        Artisan::call('migrate', ['--path' => 'functional/billing/database/migrations/2026_10_10_000065_add_source_to_transmissions_table.php']);
        $this->assertTrue(Schema::hasColumn('transmissions', 'source_id'));
    }

    /**
     * @param  array<string, int|string>  $attributes
     */
    private function assertInsertRefused(array $attributes): void
    {
        $refusal = rescue(fn () => DB::transaction(fn () => $this->insert($attributes)), fn (Throwable $exception): Throwable => $exception, report: false);

        $this->assertInstanceOf(QueryException::class, $refusal);
    }

    /**
     * @param  array<string, int|string>  $attributes
     */
    private function insert(array $attributes): void
    {
        DB::table('transmissions')->insert([...$attributes, 'uuid' => (string) Str::uuid(), 'status' => 'pending', 'created_at' => now(), 'updated_at' => now()]);
    }
}
