<?php

namespace Functional\Fleet\Tests\Unit;

use Functional\Fleet\Enums\MachineStatus;
use Functional\Fleet\Enums\MachineTransition;
use Functional\Fleet\Exceptions\IllegalMachineTransitionException;
use Functional\Fleet\States\MachineStateFactory;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class MachineStateTest extends TestCase
{
    private const TRANSITIONS = [
        'depart',
        'returnInGoodState',
        'returnToWorkshop',
        'sendToWorkshop',
        'markOutOfOrder',
        'makeAvailable',
        'retire',
    ];

    private const LEGAL_TRANSITIONS = [
        'available' => ['depart' => 'rented_out', 'sendToWorkshop' => 'workshop', 'markOutOfOrder' => 'out_of_order', 'retire' => 'retired'],
        'rented_out' => ['returnInGoodState' => 'available', 'returnToWorkshop' => 'workshop'],
        'workshop' => ['makeAvailable' => 'available', 'markOutOfOrder' => 'out_of_order', 'retire' => 'retired'],
        'out_of_order' => ['makeAvailable' => 'available', 'sendToWorkshop' => 'workshop', 'retire' => 'retired'],
        'retired' => [],
    ];

    /**
     * @return iterable<string, array{string, string}>
     */
    public static function everyStatusAndTransition(): iterable
    {
        foreach (array_keys(self::LEGAL_TRANSITIONS) as $status) {
            foreach (self::TRANSITIONS as $transition) {
                yield "{$status} → {$transition}" => [$status, $transition];
            }
        }
    }

    #[DataProvider('everyStatusAndTransition')]
    public function test_only_the_documented_transitions_are_legal(string $status, string $transition): void
    {
        $state = MachineStateFactory::fromStatus(MachineStatus::from($status));
        $expectedStatus = self::LEGAL_TRANSITIONS[$status][$transition] ?? null;

        if ($expectedStatus === null) {
            $this->expectException(IllegalMachineTransitionException::class);
        }

        $nextState = $state->{$transition}();

        $this->assertSame($expectedStatus, $nextState->status()->value);
    }

    public function test_each_state_lists_exactly_its_legal_transitions(): void
    {
        foreach (self::LEGAL_TRANSITIONS as $status => $legalTransitions) {
            $allowedTransitions = array_map(
                fn (MachineTransition $transition): string => lcfirst(str_replace('_', '', ucwords($transition->value, '_'))),
                MachineStateFactory::fromStatus(MachineStatus::from($status))->allowedTransitions(),
            );

            $this->assertEqualsCanonicalizing(array_keys($legalTransitions), $allowedTransitions, $status);
        }
    }

    public function test_only_available_and_rented_out_machines_accept_reservations(): void
    {
        $acceptingStatuses = collect(MachineStatus::cases())
            ->filter(fn (MachineStatus $status): bool => MachineStateFactory::fromStatus($status)->acceptsReservations())
            ->values()
            ->all();

        $this->assertSame([MachineStatus::Available, MachineStatus::RentedOut], $acceptingStatuses);
    }
}
