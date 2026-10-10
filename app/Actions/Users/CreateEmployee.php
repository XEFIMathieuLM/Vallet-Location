<?php

namespace App\Actions\Users;

use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;

final class CreateEmployee
{
    private const INITIAL_PASSWORD_LENGTH = 40;

    public function handle(string $name, string $email, int $agencyId): User
    {
        $employee = DB::transaction(function () use ($name, $email, $agencyId): User {
            $employee = User::query()->create([
                'name' => $name,
                'email' => Str::lower(trim($email)),
                'agency_id' => $agencyId,
                'password' => Str::password(self::INITIAL_PASSWORD_LENGTH),
            ]);
            $employee->markEmailAsVerified();
            $employee->assignRole(PermissionSeeder::EMPLOYEE_ROLE);

            return $employee;
        });

        Password::broker()->sendResetLink(['email' => $employee->email]);

        return $employee;
    }
}
