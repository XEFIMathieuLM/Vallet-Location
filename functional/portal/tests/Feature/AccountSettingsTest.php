<?php

namespace Functional\Portal\Tests\Feature;

use Functional\Booking\Models\Customer;
use Functional\Portal\Livewire\Customer\AccountSettings;
use Functional\Portal\Tests\Concerns\BuildsPortalFixtures;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;
use Tests\TestCase;

class AccountSettingsTest extends TestCase
{
    use BuildsPortalFixtures, RefreshDatabase;

    public function test_a_customer_changes_the_name_and_phone_of_the_account_without_touching_the_customer_record(): void
    {
        $customer = Customer::factory()->create(['name' => 'Terrassements Martin', 'phone' => '02 35 00 00 00']);
        $account = $this->attachedCustomerAccount($customer);
        $this->actingAs($account, 'customer');

        $this->get(route('portal.account'))->assertOk();
        Livewire::test(AccountSettings::class)
            ->set('name', 'Martin TP')
            ->set('phone', '06 12 34 56 78')
            ->call('updateProfile')
            ->assertHasNoErrors();

        $this->assertSame('Martin TP', $account->fresh()?->name);
        $this->assertSame('06 12 34 56 78', $account->fresh()?->phone);
        $this->assertSame('Terrassements Martin', $customer->fresh()?->name);
        $this->assertSame('02 35 00 00 00', $customer->fresh()?->phone);
    }

    public function test_a_customer_changes_the_password_with_the_current_one(): void
    {
        $account = $this->customerAccount();
        $this->actingAs($account, 'customer');

        Livewire::test(AccountSettings::class)
            ->set('currentPassword', 'mauvais')
            ->set('password', 'NouveauMotDePasse!2026')
            ->set('password_confirmation', 'NouveauMotDePasse!2026')
            ->call('updatePassword')
            ->assertHasErrors('currentPassword');

        Livewire::test(AccountSettings::class)
            ->set('currentPassword', 'password')
            ->set('password', 'NouveauMotDePasse!2026')
            ->set('password_confirmation', 'NouveauMotDePasse!2026')
            ->call('updatePassword')
            ->assertHasNoErrors();

        $this->assertTrue(Hash::check('NouveauMotDePasse!2026', (string) $account->fresh()?->password));
    }
}
