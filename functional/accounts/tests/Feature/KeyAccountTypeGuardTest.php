<?php

namespace Functional\Accounts\Tests\Feature;

use Functional\Accounts\Actions\RevokeKeyAccount;
use Functional\Accounts\Exceptions\KeyAccountMustStayProfessionalException;
use Functional\Accounts\Models\KeyAccount;
use Functional\Booking\Actions\UpdateCustomer;
use Functional\Booking\Enums\CustomerType;
use Functional\Fleet\Tests\Concerns\AssertsRefusals;
use Functional\Fleet\Tests\Concerns\CreatesUsers;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class KeyAccountTypeGuardTest extends TestCase
{
    use AssertsRefusals, CreatesUsers, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seedPermissions();
    }

    public function test_a_key_account_cannot_be_qualified_as_an_individual(): void
    {
        $customer = KeyAccount::factory()->create()->customer;

        $this->assertRefused(
            KeyAccountMustStayProfessionalException::class,
            'grand compte',
            fn () => app(UpdateCustomer::class)->qualify($customer, CustomerType::Individual, $this->employee()),
        );
        $this->assertSame(CustomerType::Professional, $customer->refresh()->type);
    }

    public function test_the_customer_can_be_qualified_as_an_individual_once_the_designation_is_revoked(): void
    {
        $customer = KeyAccount::factory()->create()->customer;
        app(RevokeKeyAccount::class)->handle($this->employee(), $customer);

        app(UpdateCustomer::class)->qualify($customer, CustomerType::Individual, $this->employee());

        $this->assertSame(CustomerType::Individual, $customer->refresh()->type);
    }
}
