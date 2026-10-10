<?php

namespace Functional\Portal\Tests\Unit;

use Functional\Portal\Enums\ReservationRequestStatus;
use Functional\Portal\Exceptions\IllegalReservationRequestTransitionException;
use Functional\Portal\States\ReservationRequestStateFactory;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class ReservationRequestStateTest extends TestCase
{
    private const TRANSITIONS = ['confirm', 'refuse', 'cancel', 'expire'];

    private const LEGAL_TRANSITIONS = [
        'pending' => ['confirm' => 'confirmed', 'refuse' => 'refused', 'cancel' => 'cancelled', 'expire' => 'expired'],
        'confirmed' => [],
        'refused' => [],
        'cancelled' => [],
        'expired' => [],
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
    public function test_each_transition_is_allowed_only_from_a_pending_request(string $status, string $transition): void
    {
        $state = ReservationRequestStateFactory::fromStatus(ReservationRequestStatus::from($status));
        $expectedStatus = self::LEGAL_TRANSITIONS[$status][$transition] ?? null;

        if ($expectedStatus === null) {
            $this->expectException(IllegalReservationRequestTransitionException::class);
            $this->expectExceptionMessage("Cannot {$transition} a reservation request in status [{$status}].");
        }

        $nextState = $state->{$transition}();

        $this->assertSame($expectedStatus, $nextState->status()->value);
    }

    public function test_only_a_pending_request_is_open(): void
    {
        $openStatuses = array_values(array_filter(
            ReservationRequestStatus::cases(),
            fn (ReservationRequestStatus $status): bool => ReservationRequestStateFactory::fromStatus($status)->isOpen(),
        ));

        $this->assertSame([ReservationRequestStatus::Pending], $openStatuses);
    }
}
