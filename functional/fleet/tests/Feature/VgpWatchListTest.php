<?php

namespace Functional\Fleet\Tests\Feature;

use Carbon\CarbonImmutable;
use Functional\Fleet\Enums\MachineStatus;
use Functional\Fleet\Models\Agency;
use Functional\Fleet\Models\Machine;
use Functional\Fleet\Queries\VgpWatchList;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VgpWatchListTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_lists_missing_then_expired_then_soon_expiring_vgp_of_the_agency(): void
    {
        $this->travelTo(CarbonImmutable::parse('2026-10-10 08:00'));
        $agency = Agency::factory()->create();
        $expiringAtLimit = $this->machine($agency, '2026-11-09');
        $expired = $this->machine($agency, '2026-09-01');
        $missing = $this->machine($agency, null);
        $this->machine($agency, '2026-11-10');
        $this->machine($agency, '2026-09-01', MachineStatus::Retired);
        $this->machine(Agency::factory()->create(), '2026-09-01');
        Machine::factory()->for($agency)->create(['vgp_due_date' => '2026-09-01']);

        $machines = app(VgpWatchList::class)->query($agency->id, CarbonImmutable::today()->addDays(30))->pluck('id')->all();

        $this->assertSame([$missing->id, $expired->id, $expiringAtLimit->id], $machines);
    }

    public function test_without_agency_it_covers_the_whole_network(): void
    {
        $this->machine(Agency::factory()->create(), null);
        $this->machine(Agency::factory()->create(), null);

        $this->assertSame(2, app(VgpWatchList::class)->query(null, CarbonImmutable::today()->addDays(30))->count());
    }

    private function machine(Agency $agency, ?string $dueDate, MachineStatus $status = MachineStatus::Available): Machine
    {
        return Machine::factory()
            ->for($agency)
            ->withStatus($status)
            ->subjectToVgpUntil($dueDate !== null ? CarbonImmutable::parse($dueDate) : null)
            ->create();
    }
}
