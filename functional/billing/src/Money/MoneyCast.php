<?php

namespace Functional\Billing\Money;

use Functional\Billing\Exceptions\InvalidMoneyException;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;

/**
 * @implements CastsAttributes<Money|null, mixed>
 */
final class MoneyCast implements CastsAttributes
{
    public function __construct(private readonly string $minorUnitsColumn) {}

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function get(Model $model, string $attributeName, mixed $storedAmount, array $attributes): ?Money
    {
        $storedMinorUnits = $attributes[$this->minorUnitsColumn] ?? null;

        return is_numeric($storedMinorUnits) ? Money::fromStored((int) $storedMinorUnits) : null;
    }

    /**
     * @param  array<string, mixed>  $attributes
     * @return array<string, int|null>
     */
    public function set(Model $model, string $attributeName, mixed $amount, array $attributes): array
    {
        if ($amount !== null && ! $amount instanceof Money) {
            throw InvalidMoneyException::notMoney($attributeName);
        }

        return [$this->minorUnitsColumn => $amount?->minorUnits];
    }
}
