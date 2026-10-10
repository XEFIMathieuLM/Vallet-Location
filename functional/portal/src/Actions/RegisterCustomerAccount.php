<?php

namespace Functional\Portal\Actions;

use Functional\Booking\Enums\CustomerType;
use Functional\Portal\Models\CustomerAccount;

final class RegisterCustomerAccount
{
    public function handle(string $name, string $email, string $phone, CustomerType $declaredType, string $password): CustomerAccount
    {
        $account = CustomerAccount::query()->create([
            'name' => trim($name),
            'email' => self::normalizedEmail($email),
            'phone' => trim($phone),
            'declared_type' => $declaredType,
            'password' => $password,
        ]);

        $account->sendEmailVerificationNotification();

        return $account;
    }

    public static function normalizedEmail(string $email): string
    {
        return mb_strtolower(trim($email));
    }
}
