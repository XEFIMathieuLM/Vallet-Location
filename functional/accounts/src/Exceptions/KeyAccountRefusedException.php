<?php

namespace Functional\Accounts\Exceptions;

use Functional\Booking\Models\Customer;
use Functional\Fleet\Exceptions\RefusalException;

final class KeyAccountRefusedException extends RefusalException
{
    public static function notProfessional(Customer $customer): self
    {
        return new self(
            "Customer {$customer->id} is not a professional customer and cannot be a key account.",
            'accounts::refusals.key_account_not_professional',
        );
    }

    public static function missingBillingRef(Customer $customer): self
    {
        return new self(
            "Customer {$customer->id} has no billing software reference.",
            'accounts::refusals.key_account_missing_billing_ref',
        );
    }

    public static function alreadyDesignated(Customer $customer): self
    {
        return new self(
            "Customer {$customer->id} is already a key account.",
            'accounts::refusals.key_account_already_designated',
            ['customer' => $customer->name],
        );
    }
}
