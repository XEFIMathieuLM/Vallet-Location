<?php

namespace Functional\Sales\Tests\Unit;

use Functional\Sales\Enums\SaleStatus;
use Functional\Sales\Exceptions\IllegalSaleTransitionException;
use Functional\Sales\States\SaleStateFactory;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class SaleStateTest extends TestCase
{
    private const TRANSITIONS = ['reserve', 'release', 'sell', 'cancel'];

    private const LEGAL_TRANSITIONS = [
        'listed' => ['reserve' => 'reserved', 'cancel' => 'cancelled'],
        'reserved' => ['release' => 'listed', 'sell' => 'sold', 'cancel' => 'cancelled'],
        'sold' => [],
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
    public function test_each_transition_is_either_allowed_or_refused_with_a_typed_exception(string $status, string $transition): void
    {
        $state = SaleStateFactory::fromStatus(SaleStatus::from($status));
        $expectedStatus = self::LEGAL_TRANSITIONS[$status][$transition] ?? null;

        if ($expectedStatus === null) {
            $this->expectException(IllegalSaleTransitionException::class);
        }

        $this->assertSame($expectedStatus, $state->{$transition}()->status()->value);
    }

    public function test_only_a_listed_sale_accepts_offers_and_a_new_asking_price(): void
    {
        foreach (SaleStatus::cases() as $status) {
            $state = SaleStateFactory::fromStatus($status);

            $this->assertSame($status === SaleStatus::Listed, $state->acceptsOffers());
            $this->assertSame($status === SaleStatus::Listed, $state->acceptsAskingPriceChange());
        }
    }

    public function test_a_listed_or_reserved_sale_is_open_and_keeps_its_description_editable(): void
    {
        foreach (SaleStatus::cases() as $status) {
            $isOpen = in_array($status, [SaleStatus::Listed, SaleStatus::Reserved], true);

            $this->assertSame($isOpen, SaleStateFactory::fromStatus($status)->isOpen());
            $this->assertSame($isOpen, SaleStateFactory::fromStatus($status)->acceptsDescriptionChange());
        }
    }
}
