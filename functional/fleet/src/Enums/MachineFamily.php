<?php

namespace Functional\Fleet\Enums;

use Illuminate\Support\Str;

enum MachineFamily: string
{
    case Nacelle = 'nacelle';
    case MiniExcavator = 'mini_excavator';
    case Compactor = 'compactor';
    case Scaffolding = 'scaffolding';
    case Neutral = 'neutral';

    public static function fromCategoryName(string $categoryName): self
    {
        $normalizedName = Str::lower(Str::ascii($categoryName));

        foreach (self::cases() as $family) {
            if (Str::contains($normalizedName, $family->keywords())) {
                return $family;
            }
        }

        return self::Neutral;
    }

    public function pictogramView(): string
    {
        return 'fleet::pictograms.'.match ($this) {
            self::Nacelle => 'nacelle',
            self::MiniExcavator => 'mini-pelle',
            self::Compactor => 'compacteur',
            self::Scaffolding => 'echafaudage',
            self::Neutral => 'neutral',
        };
    }

    /**
     * @return list<string>
     */
    private function keywords(): array
    {
        return match ($this) {
            self::Nacelle => ['nacelle'],
            self::MiniExcavator => ['mini-pelle', 'minipelle', 'pelle'],
            self::Compactor => ['compacteur', 'rouleau', 'plaque vibrante'],
            self::Scaffolding => ['echafaudage'],
            self::Neutral => [],
        };
    }
}
