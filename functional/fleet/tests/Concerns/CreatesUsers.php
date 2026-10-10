<?php

namespace Functional\Fleet\Tests\Concerns;

use BackedEnum;
use Functional\Fleet\Contracts\AgencyMember;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;

trait CreatesUsers
{
    /**
     * @param  array<string, mixed>  $attributes
     */
    protected function employee(array $attributes = []): Model&Authenticatable&AgencyMember
    {
        return $this->userModel()::factory()->employee()->create($attributes);
    }

    protected function userWithPermissions(BackedEnum ...$permissions): Model&Authenticatable&AgencyMember
    {
        $user = $this->userWithoutPermission();
        $user->givePermissionTo(array_map(fn (BackedEnum $permission): string|int => $permission->value, $permissions));

        return $user;
    }

    protected function userWithoutPermission(): Model&Authenticatable&AgencyMember
    {
        return $this->userModel()::factory()->create();
    }

    /**
     * @return class-string<Model&Authenticatable&AgencyMember>
     */
    private function userModel(): string
    {
        return config('auth.providers.users.model');
    }
}
