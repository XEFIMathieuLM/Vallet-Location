<?php

namespace App\Actions\Users;

use App\Exceptions\SelfDeactivationException;
use App\Models\User;
use Illuminate\Support\Carbon;

final class ChangeUserActivation
{
    public function deactivate(User $user, User $author): User
    {
        if ($user->is($author)) {
            throw new SelfDeactivationException(__('users.refusals.self_deactivation'));
        }

        $user->update(['deactivated_at' => Carbon::now()]);

        return $user;
    }

    public function reactivate(User $user): User
    {
        $user->update(['deactivated_at' => null]);

        return $user;
    }
}
