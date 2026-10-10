<?php

namespace Functional\Sales\Tests\Unit;

use Functional\Sales\Enums\OfferStatus;
use Functional\Sales\Exceptions\IllegalOfferTransitionException;
use Functional\Sales\States\OfferStateFactory;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class OfferStateTest extends TestCase
{
    private const TRANSITIONS = ['accept', 'reject', 'withdraw'];

    private const LEGAL_TRANSITIONS = [
        'pending' => ['accept' => 'accepted', 'reject' => 'rejected', 'withdraw' => 'withdrawn'],
        'accepted' => ['withdraw' => 'withdrawn'],
        'rejected' => [],
        'withdrawn' => [],
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
    public function test_each_transition_is_either_allowed_or_refused_with_a_typed_exception(string $status, string $transition): void
    {
        $state = OfferStateFactory::fromStatus(OfferStatus::from($status));
        $expectedStatus = self::LEGAL_TRANSITIONS[$status][$transition] ?? null;

        if ($expectedStatus === null) {
            $this->expectException(IllegalOfferTransitionException::class);
        }

        $this->assertSame($expectedStatus, $state->{$transition}()->status()->value);
    }
}
