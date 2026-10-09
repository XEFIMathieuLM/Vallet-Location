<?php

namespace Functional\Fleet\Database\Seeders;

use Carbon\CarbonImmutable;
use Functional\Fleet\Models\Agency;
use Functional\Fleet\Models\Machine;
use Functional\Fleet\Models\MachineCategory;
use Illuminate\Database\Seeder;

class FleetSeeder extends Seeder
{
    private const AGENCY_NAMES = ['Annecy', 'Chambéry', 'Grenoble', 'Lyon', 'Valence', 'Albertville', 'Thonon'];

    private const CATEGORIES = [
        ['name' => 'Nacelle', 'is_vgp_required' => true, 'prefix' => 'NAC'],
        ['name' => 'Chariot élévateur', 'is_vgp_required' => true, 'prefix' => 'CHA'],
        ['name' => 'Mini-pelle', 'is_vgp_required' => false, 'prefix' => 'MPE'],
        ['name' => 'Compacteur', 'is_vgp_required' => false, 'prefix' => 'CMP'],
    ];

    public function run(): void
    {
        $agencies = collect(self::AGENCY_NAMES)
            ->map(fn (string $agencyName): Agency => Agency::query()->create(['name' => $agencyName]));

        foreach (self::CATEGORIES as $categoryDefinition) {
            $category = MachineCategory::query()->create([
                'name' => $categoryDefinition['name'],
                'is_vgp_required' => $categoryDefinition['is_vgp_required'],
            ]);

            foreach (range(1, 10) as $machineNumber) {
                Machine::query()->create([
                    'reference' => sprintf('%s-%04d', $categoryDefinition['prefix'], $machineNumber),
                    'machine_category_id' => $category->id,
                    'agency_id' => $agencies[$machineNumber % $agencies->count()]->id,
                    'is_subject_to_vgp' => $categoryDefinition['is_vgp_required'],
                    'vgp_due_date' => $categoryDefinition['is_vgp_required'] ? CarbonImmutable::today()->addMonths($machineNumber) : null,
                ]);
            }
        }
    }
}
