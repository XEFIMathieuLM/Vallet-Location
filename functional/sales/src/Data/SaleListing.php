<?php

namespace Functional\Sales\Data;

use Functional\Billing\Money\Money;

final readonly class SaleListing
{
    public function __construct(
        public Money $askingPrice,
        public ?int $yearOfManufacture,
        public ?int $operatingHours,
        public string $condition,
        public ?string $comment,
    ) {}

    /**
     * @return array{year_of_manufacture: int|null, operating_hours: int|null, condition: string, comment: string|null}
     */
    public function description(): array
    {
        return [
            'year_of_manufacture' => $this->yearOfManufacture,
            'operating_hours' => $this->operatingHours,
            'condition' => $this->condition,
            'comment' => $this->comment,
        ];
    }
}
