<?php

namespace Functional\Fleet\Tests\Unit;

use Functional\Fleet\Enums\MachineFamily;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class MachineFamilyTest extends TestCase
{
    /**
     * @return array<string, array{string, MachineFamily}>
     */
    public static function categoryNames(): array
    {
        return [
            'nacelle 12 m' => ['Nacelle 12 m', MachineFamily::Nacelle],
            'nacelle 20 m' => ['Nacelle 20 m', MachineFamily::Nacelle],
            'mini-pelle' => ['Mini-pelle 1.8 t', MachineFamily::MiniExcavator],
            'minipelle' => ['Minipelle', MachineFamily::MiniExcavator],
            'pelle' => ['Pelle 8 t', MachineFamily::MiniExcavator],
            'compacteur' => ['Compacteur', MachineFamily::Compactor],
            'rouleau' => ['Rouleau tandem', MachineFamily::Compactor],
            'plaque vibrante' => ['Plaque vibrante', MachineFamily::Compactor],
            'échafaudage' => ['Échafaudage 40 m²', MachineFamily::Scaffolding],
            'other category' => ['Groupe électrogène', MachineFamily::Neutral],
            'empty name' => ['', MachineFamily::Neutral],
        ];
    }

    #[DataProvider('categoryNames')]
    public function test_a_category_name_maps_to_its_machine_family(string $categoryName, MachineFamily $expectedFamily): void
    {
        $this->assertSame($expectedFamily, MachineFamily::fromCategoryName($categoryName));
    }

    public function test_each_family_names_its_pictogram_view(): void
    {
        $this->assertSame('fleet::pictograms.nacelle', MachineFamily::Nacelle->pictogramView());
        $this->assertSame('fleet::pictograms.mini-pelle', MachineFamily::MiniExcavator->pictogramView());
        $this->assertSame('fleet::pictograms.compacteur', MachineFamily::Compactor->pictogramView());
        $this->assertSame('fleet::pictograms.echafaudage', MachineFamily::Scaffolding->pictogramView());
        $this->assertSame('fleet::pictograms.neutral', MachineFamily::Neutral->pictogramView());
    }
}
