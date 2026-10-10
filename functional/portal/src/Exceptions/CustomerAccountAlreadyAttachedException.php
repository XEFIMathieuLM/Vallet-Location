<?php

namespace Functional\Portal\Exceptions;

use Functional\Fleet\Exceptions\RefusalException;
use Functional\Portal\Models\CustomerAccount;

final class CustomerAccountAlreadyAttachedException extends RefusalException
{
    public static function for(CustomerAccount $account): self
    {
        return new self(
            "Customer account {$account->id} is already attached to a customer record.",
            'portal::refusals.already_attached',
        );
    }
}
