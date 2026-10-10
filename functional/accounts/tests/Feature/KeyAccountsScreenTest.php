<?php

namespace Functional\Accounts\Tests\Feature;

use Functional\Accounts\Access\AccountsPermission;
use Functional\Accounts\Livewire\KeyAccounts;
use Functional\Accounts\Models\KeyAccount;
use Functional\Billing\Models\CustomerBillingAccount;
use Functional\Booking\Models\Customer;
use Functional\Fleet\Tests\Concerns\CreatesUsers;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class KeyAccountsScreenTest extends TestCase
{
    use CreatesUsers, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seedPermissions();
    }

    public function test_the_screen_requires_the_key_accounts_permission(): void
    {
        $this->actingAs($this->userWithoutPermission())->get(route('accounts.key-accounts'))->assertForbidden();
        $this->actingAs($this->userWithPermissions(AccountsPermission::ManageKeyAccounts))->get(route('accounts.key-accounts'))->assertOk();
    }

    public function test_the_screen_lists_key_accounts_with_their_billing_ref(): void
    {
        $keyAccount = KeyAccount::factory()->create();
        CustomerBillingAccount::factory()->for($keyAccount->customer)->create(['external_ref' => 'CLI-00042']);

        $this->actingAs($this->employee());
        Livewire::test(KeyAccounts::class)
            ->assertSee($keyAccount->customer->name)
            ->assertSee('CLI-00042');
    }

    public function test_the_search_only_offers_professional_customers(): void
    {
        Customer::factory()->professional()->create(['name' => 'Bâti-Ouest']);
        Customer::factory()->individual()->create(['name' => 'Bâti Martin']);

        $this->actingAs($this->employee());
        Livewire::test(KeyAccounts::class)
            ->set('search', 'Bâti')
            ->assertSee('Bâti-Ouest')
            ->assertDontSee('Bâti Martin');
    }

    public function test_a_customer_is_designated_and_revoked_from_the_screen(): void
    {
        $customer = Customer::factory()->professional()->create();
        CustomerBillingAccount::factory()->for($customer)->create();

        $this->actingAs($this->employee());
        $component = Livewire::test(KeyAccounts::class)->call('designate', $customer->id)->assertHasNoErrors();
        $this->assertTrue(KeyAccount::query()->where('customer_id', $customer->id)->exists());

        $component->call('revoke', $customer->id)->assertHasNoErrors();
        $this->assertFalse(KeyAccount::query()->where('customer_id', $customer->id)->exists());
    }

    public function test_a_refusal_is_displayed_on_the_screen(): void
    {
        $customer = Customer::factory()->professional()->create();

        $this->actingAs($this->employee());
        Livewire::test(KeyAccounts::class)
            ->call('designate', $customer->id)
            ->assertHasErrors('refusal')
            ->assertSee('identifiant de facturation');
    }
}
