<?php

namespace Functional\Portal\Tests\Feature\Auth;

use Functional\Booking\Enums\CustomerType;
use Functional\Booking\Models\Customer;
use Functional\Portal\Livewire\Auth\Register;
use Functional\Portal\Models\CustomerAccount;
use Functional\Portal\Notifications\VerifyCustomerEmail;
use Functional\Portal\Tests\Concerns\BuildsPortalFixtures;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use BuildsPortalFixtures, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Notification::fake();
    }

    public function test_the_registration_page_is_open_to_visitors(): void
    {
        $this->get(route('portal.register'))->assertOk()->assertSee(__('portal::auth.register.title'));
    }

    public function test_a_visitor_creates_a_professional_account_and_is_asked_to_confirm_the_address(): void
    {
        $this->fillRegistration(['email' => '  Chantier@Exemple.FR '])
            ->call('register')
            ->assertHasNoErrors()
            ->assertRedirect(route('portal.verification.notice'));

        $account = CustomerAccount::query()->sole();
        $this->assertSame('chantier@exemple.fr', $account->email);
        $this->assertSame(CustomerType::Professional, $account->declared_type);
        $this->assertNull($account->email_verified_at);
        $this->assertTrue(Auth::guard('customer')->user()?->is($account));
        Notification::assertSentTo($account, VerifyCustomerEmail::class);
    }

    public function test_registration_without_a_customer_type_is_refused(): void
    {
        $this->fillRegistration(['declaredType' => ''])
            ->call('register')
            ->assertHasErrors(['declaredType' => 'required']);

        $this->assertSame(0, CustomerAccount::query()->count());
    }

    public function test_an_email_already_used_by_another_customer_account_is_refused(): void
    {
        $this->customerAccount(['email' => 'chantier@exemple.fr']);

        $this->fillRegistration(['email' => 'CHANTIER@exemple.fr'])
            ->call('register')
            ->assertHasErrors(['email' => 'unique']);

        $this->assertSame(1, CustomerAccount::query()->count());
    }

    public function test_registration_never_creates_or_attaches_a_customer_record(): void
    {
        Customer::factory()->create(['email' => 'chantier@exemple.fr']);

        $this->fillRegistration(['email' => 'chantier@exemple.fr'])->call('register')->assertHasNoErrors();

        $this->assertSame(1, Customer::query()->count());
        $this->assertNull(CustomerAccount::query()->sole()->customer_id);
    }

    /**
     * @param  array<string, string>  $overrides
     */
    private function fillRegistration(array $overrides = []): mixed
    {
        $fields = [
            'name' => 'Terrassements Martin',
            'email' => 'contact@terrassements-martin.fr',
            'phone' => '02 35 12 34 56',
            'declaredType' => CustomerType::Professional->value,
            'password' => 'MotDePasse!2026',
            'password_confirmation' => 'MotDePasse!2026',
            ...$overrides,
        ];

        $component = Livewire::test(Register::class);

        foreach ($fields as $field => $fieldValue) {
            $component->set($field, $fieldValue);
        }

        return $component;
    }
}
