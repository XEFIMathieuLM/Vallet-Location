<?php

namespace Functional\Fleet\Tests\Feature;

use Functional\Fleet\Enums\MachineStatus;
use Functional\Fleet\Models\Agency;
use Functional\Fleet\Models\Machine;
use Functional\Fleet\Queries\FleetStatusCounts;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class FleetStatusCountsTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_counts_the_machines_of_an_agency_by_status_without_retired_ones(): void
    {
        $agency = Agency::factory()->create();
        Machine::factory()->for($agency)->count(3)->create();
        Machine::factory()->for($agency)->withStatus(MachineStatus::Workshop)->create();
        Machine::factory()->for($agency)->withStatus(MachineStatus::Retired)->count(2)->create();
        Machine::factory()->withStatus(MachineStatus::OutOfOrder)->create();

        $this->assertSame([
            'available' => 3,
            'rented_out' => 0,
            'workshop' => 1,
            'out_of_order' => 0,
        ], app(FleetStatusCounts::class)->count($agency->id));
    }

    public function test_without_agency_it_counts_the_whole_network_in_a_single_query(): void
    {
        Machine::factory()->count(2)->create();
        Machine::factory()->withStatus(MachineStatus::RentedOut)->create();
        Machine::factory()->withStatus(MachineStatus::OutOfOrder)->create();

        DB::enableQueryLog();
        $counts = app(FleetStatusCounts::class)->count(null);

        $this->assertCount(1, DB::getQueryLog());
        $this->assertSame(['available' => 2, 'rented_out' => 1, 'workshop' => 0, 'out_of_order' => 1], $counts);
    }
}
