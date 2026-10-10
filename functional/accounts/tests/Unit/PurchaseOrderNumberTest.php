<?php

namespace Functional\Accounts\Tests\Unit;

use Functional\Accounts\Exceptions\InvalidPurchaseOrderNumberException;
use Functional\Accounts\Support\PurchaseOrderNumber;
use Functional\Fleet\Tests\Concerns\AssertsRefusals;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class PurchaseOrderNumberTest extends TestCase
{
    use AssertsRefusals;

    public function test_leading_and_trailing_spaces_are_removed(): void
    {
        $this->assertSame('BC-2026-0412', PurchaseOrderNumber::normalize('  BC-2026-0412 '));
    }

    public function test_a_number_of_fifty_characters_is_accepted(): void
    {
        $this->assertSame(str_repeat('A', 50), PurchaseOrderNumber::normalize(str_repeat('A', 50)));
    }

    /**
     * @return iterable<string, array{string, string}>
     */
    public static function invalidNumbers(): iterable
    {
        yield 'empty' => ['', 'saisi'];
        yield 'only spaces' => ['    ', 'saisi'];
        yield 'fifty-one characters' => [str_repeat('A', 51), '50 caractères'];
    }

    #[DataProvider('invalidNumbers')]
    public function test_an_invalid_number_is_refused(string $rawNumber, string $expectedUserText): void
    {
        $this->assertRefused(InvalidPurchaseOrderNumberException::class, $expectedUserText, fn () => PurchaseOrderNumber::normalize($rawNumber));
    }
}
