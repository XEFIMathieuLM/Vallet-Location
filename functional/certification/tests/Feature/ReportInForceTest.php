<?php

namespace Functional\Certification\Tests\Feature;

use Functional\Certification\Models\VgpReport;
use Functional\Certification\Queries\ReportInForce;
use Functional\Fleet\Models\Machine;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ReportInForceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('vgp-reports');
    }

    public function test_the_last_deposited_report_is_in_force(): void
    {
        $machine = Machine::factory()->vgpValid()->create();
        VgpReport::factory()->for($machine)->create();
        $latestReport = VgpReport::factory()->for($machine)->create();

        $this->assertTrue($latestReport->is(app(ReportInForce::class)->for($machine)));
        $this->assertNull(app(ReportInForce::class)->for(Machine::factory()->vgpValid()->create()));
    }

    public function test_the_reports_in_force_of_many_machines_are_read_in_one_query(): void
    {
        $machines = Machine::factory()->vgpValid()->count(3)->create();
        $latestReports = $machines->map(fn (Machine $machine): VgpReport => VgpReport::factory()->count(2)->for($machine)->create()->last());

        DB::enableQueryLog();
        $reportsInForce = app(ReportInForce::class)->forMachines($machines->pluck('id'));

        $this->assertCount(1, DB::getQueryLog());
        $this->assertEqualsCanonicalizing($latestReports->pluck('id')->all(), $reportsInForce->pluck('id')->all());
    }
}
