<?php

namespace Functional\Accounts\Exceptions;

use Functional\Booking\Models\Customer;
use Functional\Fleet\Exceptions\RefusalException;

final class KeyAccountMustStayProfessionalException extends RefusalException
{
    public static function forCustomer(Customer $customer): self
    {
        return new self(
            "Customer {$customer->id} is a key account and must stay a professional customer.",
            'accounts::refusals.key_account_must_stay_professional',
            ['customer' => $customer->name],
        );
    }
}
