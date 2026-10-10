<?php

namespace Functional\Deposit\Tests\Unit;

use Functional\Billing\Money\Money;
use Functional\Deposit\ValueObjects\DepositRetention;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class DepositRetentionTest extends TestCase
{
    /**
     * @return iterable<string, array{int, int, int, int}>
     */
    public static function retentions(): iterable
    {
        yield 'damages below the deposit' => [150000, 45000, 45000, 105000];
        yield 'damages above the deposit' => [150000, 200000, 150000, 0];
        yield 'no billed damage' => [150000, 0, 0, 150000];
        yield 'damages equal to the deposit' => [150000, 150000, 150000, 0];
    }

    #[DataProvider('retentions')]
    public function test_the_retention_is_the_billed_total_capped_at_the_deposit(int $depositCents, int $billedCents, int $retainedCents, int $refundedCents): void
    {
        $retention = DepositRetention::fromBilledTotal(Money::fromStored($depositCents), Money::fromStored($billedCents));

        $this->assertSame($retainedCents, $retention->retained->minorUnits);
        $this->assertSame($refundedCents, $retention->refunded->minorUnits);
        $this->assertSame($depositCents, $retention->retained->plus($retention->refunded)->minorUnits);
    }
}
