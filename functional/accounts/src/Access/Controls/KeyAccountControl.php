<?php

namespace Functional\Accounts\Access\Controls;

use Functional\Accounts\Access\AccountsPermission;
use Functional\Accounts\Models\KeyAccount;
use Functional\Fleet\Access\Perimeters\GlobalPerimeter;
use Illuminate\Contracts\Auth\Access\Authorizable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Lomkit\Access\Controls\Control;

class KeyAccountControl extends Control
{
    /**
     * @var class-string<Model>
     */
    protected string $model = KeyAccount::class;

    protected function perimeters(): array
    {
        return [
            GlobalPerimeter::new()
                ->allowed(fn (Model $user, string $method): bool => $user instanceof Authorizable && $user->can(AccountsPermission::ManageKeyAccounts->value))
                ->should(fn (Model $user, Model $keyAccount): bool => true)
                ->query(fn (Builder $query, Model $user): Builder => $query),
        ];
    }
}
