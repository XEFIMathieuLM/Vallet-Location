<?php

namespace Functional\Billing\Money;

use Functional\Billing\Exceptions\CurrencyMismatchException;
use Functional\Billing\Exceptions\InvalidMoneyException;

final readonly class Money
{
    public const INPUT_PATTERN = '/^\d{1,7}([.,]\d{1,2})?$/';

    private function __construct(public int $minorUnits, public Currency $currency) {}

    public static function fromInput(string $typedAmount, Currency $currency = Currency::Eur): self
    {
        $normalizedAmount = trim($typedAmount);

        if (preg_match(self::INPUT_PATTERN, $normalizedAmount) !== 1) {
            throw InvalidMoneyException::malformed($typedAmount);
        }

        [$units, $fraction] = array_pad(explode('.', str_replace(',', '.', $normalizedAmount)), 2, '');

        return new self((int) $units * $currency->minorUnitsPerUnit() + (int) str_pad($fraction, 2, '0'), $currency);
    }

    public static function fromStored(int $minorUnits, Currency $currency = Currency::Eur): self
    {
        return new self($minorUnits, $currency);
    }

    public static function zero(Currency $currency = Currency::Eur): self
    {
        return new self(0, $currency);
    }

    public function plus(self $other): self
    {
        if ($other->currency !== $this->currency) {
            throw CurrencyMismatchException::between($this->currency, $other->currency);
        }

        return new self($this->minorUnits + $other->minorUnits, $this->currency);
    }

    public function isPositive(): bool
    {
        return $this->minorUnits > 0;
    }

    public function equals(self $other): bool
    {
        return $this->minorUnits === $other->minorUnits && $this->currency === $other->currency;
    }

    public function format(): string
    {
        $minorUnitsPerUnit = $this->currency->minorUnitsPerUnit();

        return sprintf('%d,%02d', intdiv($this->minorUnits, $minorUnitsPerUnit), $this->minorUnits % $minorUnitsPerUnit);
    }
}
