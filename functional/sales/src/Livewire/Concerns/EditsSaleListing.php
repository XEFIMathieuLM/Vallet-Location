<?php

namespace Functional\Sales\Livewire\Concerns;

use Carbon\CarbonImmutable;
use Functional\Billing\Money\Money;
use Functional\Sales\Data\SaleListing;

trait EditsSaleListing
{
    public string $askingPrice = '';

    public string $yearOfManufacture = '';

    public string $operatingHours = '';

    public string $condition = '';

    public string $comment = '';

    /**
     * @return array<string, list<string>>
     */
    protected function listingRules(): array
    {
        return [
            'askingPrice' => ['required', 'regex:'.Money::INPUT_PATTERN],
            'yearOfManufacture' => ['nullable', 'integer', 'min:1950', 'max:'.CarbonImmutable::now()->year],
            'operatingHours' => ['nullable', 'integer', 'min:0'],
            'condition' => ['required', 'string', 'max:255'],
            'comment' => ['nullable', 'string', 'max:2000'],
        ];
    }

    /**
     * @return array<string, string>
     */
    protected function listingAttributes(): array
    {
        return [
            'askingPrice' => __('sales::sales.fields.asking_price'),
            'yearOfManufacture' => __('sales::sales.fields.year_of_manufacture'),
            'operatingHours' => __('sales::sales.fields.operating_hours'),
            'condition' => __('sales::sales.fields.condition'),
            'comment' => __('sales::sales.fields.comment'),
        ];
    }

    protected function listing(Money $askingPrice): SaleListing
    {
        return new SaleListing(
            $askingPrice,
            $this->yearOfManufacture !== '' ? (int) $this->yearOfManufacture : null,
            $this->operatingHours !== '' ? (int) $this->operatingHours : null,
            $this->condition,
            $this->comment !== '' ? $this->comment : null,
        );
    }
}
