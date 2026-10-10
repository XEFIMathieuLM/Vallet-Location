<?php

namespace Functional\Billing\Tests\Unit;

use Functional\Billing\Exceptions\CurrencyMismatchException;
use Functional\Billing\Exceptions\InvalidMoneyException;
use Functional\Billing\Money\Currency;
use Functional\Billing\Money\Money;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class MoneyTest extends TestCase
{
    /**
     * @return iterable<string, array{string, int}>
     */
    public static function typedAmounts(): iterable
    {
        yield 'whole euros' => ['450', 45000];
        yield 'comma decimals' => ['450,50', 45050];
        yield 'dot decimals' => ['450.5', 45050];
        yield 'surrounding spaces' => [' 12,05 ', 1205];
        yield 'zero' => ['0', 0];
    }

    #[DataProvider('typedAmounts')]
    public function test_a_typed_amount_is_read_exactly_in_cents(string $typedAmount, int $expectedMinorUnits): void
    {
        $this->assertSame($expectedMinorUnits, Money::fromInput($typedAmount)->minorUnits);
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function invalidAmounts(): iterable
    {
        yield 'empty' => [''];
        yield 'letters' => ['abc'];
        yield 'negative' => ['-10'];
        yield 'three decimals' => ['1,005'];
    }

    #[DataProvider('invalidAmounts')]
    public function test_an_invalid_typed_amount_is_refused(string $typedAmount): void
    {
        $this->expectException(InvalidMoneyException::class);

        Money::fromInput($typedAmount);
    }

    public function test_amounts_add_up_formats_with_a_decimal_comma_and_compare_by_value(): void
    {
        $total = Money::fromInput('450,50')->plus(Money::fromStored(1205));

        $this->assertSame('462,55', $total->format());
        $this->assertTrue($total->equals(Money::fromStored(46255)));
        $this->assertTrue($total->isPositive());
        $this->assertFalse(Money::zero()->isPositive());
        $this->assertSame(Currency::Eur, $total->currency);
    }

    public function test_amounts_of_different_currencies_cannot_be_added(): void
    {
        $this->expectException(CurrencyMismatchException::class);

        Money::zero(Currency::Eur)->plus(Money::fromStored(100, Currency::Chf));
    }
}
