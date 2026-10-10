<?php

namespace Functional\Booking\Tests\Unit;

use Functional\Booking\Enums\ReservationStatus;
use Functional\Booking\Exceptions\IllegalReservationTransitionException;
use Functional\Booking\States\ReservationStateFactory;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class ReservationStateTest extends TestCase
{
    private const TRANSITIONS = ['depart', 'returnMachine', 'cancel'];

    private const LEGAL_TRANSITIONS = [
        'confirmed' => ['depart' => 'in_progress', 'cancel' => 'cancelled'],
        'in_progress' => ['returnMachine' => 'closed'],
        'closed' => [],
        'cancelled' => [],
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
        $state = ReservationStateFactory::fromStatus(ReservationStatus::from($status));
        $expectedStatus = self::LEGAL_TRANSITIONS[$status][$transition] ?? null;

        if ($expectedStatus === null) {
            $this->expectException(IllegalReservationTransitionException::class);
        }

        $nextState = $state->{$transition}();

        $this->assertSame($expectedStatus, $nextState->status()->value);
    }
}
