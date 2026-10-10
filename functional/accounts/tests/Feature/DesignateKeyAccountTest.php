<?php

namespace Functional\Accounts\Tests\Feature;

use Functional\Accounts\Actions\DesignateKeyAccount;
use Functional\Accounts\Exceptions\KeyAccountRefusedException;
use Functional\Accounts\Models\KeyAccount;
use Functional\Billing\Models\CustomerBillingAccount;
use Functional\Booking\Events\CustomerChanged;
use Functional\Booking\Models\Customer;
use Functional\Fleet\Tests\Concerns\AssertsRefusals;
use Functional\Fleet\Tests\Concerns\CreatesUsers;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Spatie\Activitylog\Models\Activity;
use Tests\TestCase;

class DesignateKeyAccountTest extends TestCase
{
    use AssertsRefusals, CreatesUsers, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seedPermissions();
    }

    public function test_a_professional_customer_with_a_billing_ref_is_designated(): void
    {
        Event::fake([CustomerChanged::class]);
        $author = $this->employee();
        $customer = Customer::factory()->professional()->create();
        CustomerBillingAccount::factory()->for($customer)->create();

        app(DesignateKeyAccount::class)->handle($author, $customer);

        $keyAccount = KeyAccount::query()->where('customer_id', $customer->id)->sole();
        $this->assertSame($author->getKey(), $keyAccount->designated_by);
        $activity = Activity::query()->where('log_name', 'accounts')->where('event', 'key_account_designated')->sole();
        $this->assertSame($customer->id, $activity->subject_id);
        $this->assertSame($author->getKey(), $activity->causer_id);
        $this->assertSame($author->agencyId(), $activity->getProperty('author_agency_id'));
        Event::assertDispatched(CustomerChanged::class, fn (CustomerChanged $event): bool => $event->customer->is($customer) && $event->changedAttributes === ['key_account']);
    }

    public function test_an_individual_customer_cannot_be_designated(): void
    {
        $customer = Customer::factory()->individual()->create();
        CustomerBillingAccount::factory()->for($customer)->create();

        $this->assertRefused(KeyAccountRefusedException::class, 'professionnel', fn () => app(DesignateKeyAccount::class)->handle($this->employee(), $customer));
        $this->assertSame(0, KeyAccount::query()->count());
    }

    public function test_a_customer_without_type_cannot_be_designated(): void
    {
        $customer = Customer::factory()->untyped()->create();
        CustomerBillingAccount::factory()->for($customer)->create();

        $this->assertRefused(KeyAccountRefusedException::class, 'professionnel', fn () => app(DesignateKeyAccount::class)->handle($this->employee(), $customer));
    }

    public function test_a_customer_without_billing_ref_cannot_be_designated(): void
    {
        $customer = Customer::factory()->professional()->create();

        $this->assertRefused(KeyAccountRefusedException::class, 'identifiant de facturation', fn () => app(DesignateKeyAccount::class)->handle($this->employee(), $customer));
    }

    public function test_a_customer_with_an_empty_billing_ref_cannot_be_designated(): void
    {
        $customer = Customer::factory()->professional()->create();
        CustomerBillingAccount::factory()->for($customer)->create(['external_ref' => '']);

        $this->assertRefused(KeyAccountRefusedException::class, 'identifiant de facturation', fn () => app(DesignateKeyAccount::class)->handle($this->employee(), $customer));
    }

    public function test_a_key_account_cannot_be_designated_twice(): void
    {
        $customer = Customer::factory()->professional()->create();
        CustomerBillingAccount::factory()->for($customer)->create();
        KeyAccount::factory()->for($customer)->create();

        $this->assertRefused(KeyAccountRefusedException::class, 'déjà', fn () => app(DesignateKeyAccount::class)->handle($this->employee(), $customer));
        $this->assertSame(1, KeyAccount::query()->count());
    }
}
