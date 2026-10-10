<?php

namespace Functional\Deposit\Tests\Unit;

use Functional\Deposit\Enums\DepositStatus;
use Functional\Deposit\Exceptions\IllegalDepositTransitionException;
use Functional\Deposit\States\DepositStateFactory;
use Functional\Fleet\Tests\Concerns\AssertsRefusals;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class DepositStatesTest extends TestCase
{
    use AssertsRefusals;

    private const TRANSITIONS = ['toRefund', 'blockByDamage', 'toSettle', 'refund', 'settle'];

    private const LEGAL_TRANSITIONS = [
        'collected' => ['toRefund' => 'to_refund', 'blockByDamage' => 'blocked_by_damage'],
        'to_refund' => ['blockByDamage' => 'blocked_by_damage', 'refund' => 'refunded'],
        'blocked_by_damage' => ['toSettle' => 'to_settle', 'toRefund' => 'to_refund'],
        'to_settle' => ['blockByDamage' => 'blocked_by_damage', 'settle' => 'settled'],
        'refunded' => [],
        'settled' => [],
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
    public function test_each_transition_is_allowed_only_from_its_legal_statuses(string $status, string $transition): void
    {
        $state = DepositStateFactory::fromStatus(DepositStatus::from($status));
        $expectedStatus = self::LEGAL_TRANSITIONS[$status][$transition] ?? null;

        if ($expectedStatus === null) {
            $this->assertRefused(IllegalDepositTransitionException::class, 'Impossible', fn () => $state->{$transition}());

            return;
        }

        $this->assertSame($expectedStatus, $state->{$transition}()->status()->value);
    }

    public function test_only_refunded_and_settled_deposits_are_final(): void
    {
        $finalStatuses = array_filter(DepositStatus::cases(), fn (DepositStatus $status): bool => $status->isFinal());

        $this->assertSame([DepositStatus::Refunded, DepositStatus::Settled], array_values($finalStatuses));
    }
}
