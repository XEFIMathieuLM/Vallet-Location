<?php

namespace Functional\Deposit\Livewire\Concerns;

use Functional\Billing\Money\Money;

trait ValidatesDepositAmount
{
    protected function validatedAmount(string $property): Money
    {
        $this->validate(
            [$property => ['required', 'regex:'.Money::INPUT_PATTERN, 'not_regex:/^0+([.,]0+)?$/']],
            [$property.'.regex' => __('deposit::rates.amount_format'), $property.'.not_regex' => __('deposit::refusals.invalid_amount')],
            [$property => __('deposit::rates.amount')],
        );

        return Money::fromInput((string) $this->{$property});
    }
}
