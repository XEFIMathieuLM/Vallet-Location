<?php

namespace App\Exceptions;

use App\Models\User;
use Functional\Fleet\Exceptions\RefusalException;

final class SelfDeactivationException extends RefusalException
{
    public static function for(User $user): self
    {
        return new self(
            "User {$user->id} cannot deactivate their own account.",
            'users.refusals.self_deactivation',
        );
    }
}
