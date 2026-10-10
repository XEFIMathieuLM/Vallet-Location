<?php

namespace Functional\Fleet\Database\Seeders;

use Functional\Fleet\Database\Factories\MachineFactory;
use Functional\Fleet\Enums\MachineStatus;
use Functional\Fleet\Models\Agency;
use Functional\Fleet\Models\Machine;
use Functional\Fleet\Models\MachineCategory;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\Sequence;
use Illuminate\Database\Seeder;

class FleetSeeder extends Seeder
{
    private const AGENCY_COUNT = 7;

    private const CATEGORY_COUNT_PER_VGP_RULE = 2;

    public function run(): void
    {
        $agencies = Agency::factory()->count(self::AGENCY_COUNT)->create();

        MachineCategory::factory()->requiringVgp()->count(self::CATEGORY_COUNT_PER_VGP_RULE)->create()
            ->each(fn (MachineCategory $category) => $this->seedVgpMachines($category, $agencies));

        MachineCategory::factory()->count(self::CATEGORY_COUNT_PER_VGP_RULE)->create()
            ->each(fn (MachineCategory $category) => $this->seedMachines($category, $agencies));
    }

    /**
     * @param  Collection<int, Agency>  $agencies
     */
    private function seedVgpMachines(MachineCategory $category, Collection $agencies): void
    {
        $this->machinesOf($category, $agencies)->vgpValid()->count(5)->create();
        $this->machinesOf($category, $agencies)->vgpExpiringSoon()->create();
        $this->machinesOf($category, $agencies)->vgpExpired()->create();
        $this->machinesOf($category, $agencies)->vgpMissing()->create();
        $this->machinesOf($category, $agencies)->vgpValid()->withStatus(MachineStatus::Workshop)->create();
        $this->machinesOf($category, $agencies)->vgpValid()->withStatus(MachineStatus::RentedOut)->count(2)->create();
    }

    /**
     * @param  Collection<int, Agency>  $agencies
     */
    private function seedMachines(MachineCategory $category, Collection $agencies): void
    {
        $this->machinesOf($category, $agencies)->count(6)->create();
        $this->machinesOf($category, $agencies)->withStatus(MachineStatus::Workshop)->create();
        $this->machinesOf($category, $agencies)->withStatus(MachineStatus::OutOfOrder)->create();
        $this->machinesOf($category, $agencies)->withStatus(MachineStatus::RentedOut)->create();
        $this->machinesOf($category, $agencies)->withStatus(MachineStatus::Retired)->create();
    }

    /**
     * @param  Collection<int, Agency>  $agencies
     */
    private function machinesOf(MachineCategory $category, Collection $agencies): MachineFactory
    {
        return Machine::factory()
            ->for($category, 'category')
            ->state(new Sequence(...$agencies->map(fn (Agency $agency): array => ['agency_id' => $agency->id])->shuffle()->all()));
    }
}
