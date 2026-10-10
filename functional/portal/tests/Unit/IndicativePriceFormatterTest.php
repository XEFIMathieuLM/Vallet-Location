<?php

namespace Functional\Portal\Tests\Unit;

use Functional\Portal\Pricing\IndicativePriceFormatter;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class IndicativePriceFormatterTest extends TestCase
{
    /**
     * @return iterable<string, array{int, string}>
     */
    public static function amounts(): iterable
    {
        yield 'euros ronds' => [9500, '95,00 €'];
        yield 'centimes' => [9999, '99,99 €'];
        yield 'milliers' => [123456, '1 234,56 €'];
    }

    #[DataProvider('amounts')]
    public function test_an_amount_in_cents_is_written_in_french_euros(int $amountCents, string $expectedAmount): void
    {
        $writtenAmount = (new IndicativePriceFormatter)->amount($amountCents);

        $this->assertSame($expectedAmount, preg_replace('/\s/u', ' ', $writtenAmount));
    }

    /**
     * @return iterable<string, array{string, int|null}>
     */
    public static function typedAmounts(): iterable
    {
        yield 'entier' => ['95', 9500];
        yield 'virgule' => ['95,5', 9550];
        yield 'point' => ['95.50', 9550];
        yield 'espaces' => [' 120 ', 12000];
        yield 'zéro' => ['0', null];
        yield 'négatif' => ['-5', null];
        yield 'texte' => ['abc', null];
        yield 'trois décimales' => ['9,999', null];
    }

    #[DataProvider('typedAmounts')]
    public function test_a_typed_amount_in_euros_becomes_strictly_positive_cents(string $typedAmount, ?int $expectedCents): void
    {
        $this->assertSame($expectedCents, (new IndicativePriceFormatter)->centsFromEuros($typedAmount));
    }
}
