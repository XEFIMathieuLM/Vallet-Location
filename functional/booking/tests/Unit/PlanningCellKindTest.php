<?php

namespace Functional\Booking\Tests\Unit;

use Functional\Booking\Planning\PlanningCellKind;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class PlanningCellKindTest extends TestCase
{
    /**
     * @return array<string, array{PlanningCellKind, string}>
     */
    public static function cellClasses(): array
    {
        return [
            'free' => [PlanningCellKind::Free, 'bg-transparent'],
            'reserved' => [PlanningCellKind::Reserved, 'bg-black text-white dark:bg-white dark:text-black'],
            'workshop' => [PlanningCellKind::Workshop, 'bg-amber-300 text-black'],
            'out of order' => [PlanningCellKind::OutOfOrder, 'bg-red-500 text-black'],
            'vgp invalid' => [PlanningCellKind::VgpInvalid, 'bg-zinc-200 text-zinc-800 dark:bg-zinc-600 dark:text-white'],
        ];
    }

    #[DataProvider('cellClasses')]
    public function test_each_cell_state_uses_the_semantic_palette(PlanningCellKind $cellKind, string $expectedClasses): void
    {
        $this->assertSame($expectedClasses, $cellKind->cssClasses());
    }

    public function test_no_cell_state_borrows_the_accent_yellow_or_the_former_blue(): void
    {
        foreach (PlanningCellKind::cases() as $cellKind) {
            $this->assertDoesNotMatchRegularExpression('/accent|yellow|blue/', $cellKind->cssClasses());
        }
    }
}
