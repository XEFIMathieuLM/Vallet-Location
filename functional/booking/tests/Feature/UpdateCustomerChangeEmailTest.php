<?php

namespace Functional\Booking\Tests\Feature;

use Functional\Booking\Actions\UpdateCustomer;
use Functional\Booking\Events\CustomerChanged;
use Functional\Booking\Exceptions\InvalidCustomerEmailException;
use Functional\Booking\Models\Customer;
use Functional\Booking\Tests\Concerns\WithoutTransitionExtensions;
use Functional\Fleet\Contracts\AgencyMember;
use Functional\Fleet\Tests\Concerns\AssertsRefusals;
use Functional\Fleet\Tests\Concerns\CreatesUsers;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Spatie\Activitylog\Models\Activity;
use Tests\TestCase;

class UpdateCustomerChangeEmailTest extends TestCase
{
    use AssertsRefusals, CreatesUsers, RefreshDatabase, WithoutTransitionExtensions;

    private Model&Authenticatable&AgencyMember $author;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seedPermissions();
        $this->author = $this->employee();
        $this->actingAs($this->author);
    }

    public function test_the_email_is_changed_announced_and_logged(): void
    {
        Event::fake([CustomerChanged::class]);
        $customer = Customer::factory()->create(['email' => null, 'phone' => '0601020304']);

        app(UpdateCustomer::class)->changeEmail($customer, ' Chantier@Exemple.fr ', $this->author);

        $this->assertSame('chantier@exemple.fr', $customer->fresh()?->email);
        Event::assertDispatched(CustomerChanged::class, fn (CustomerChanged $event): bool => $event->customer->is($customer) && $event->changedAttributes === ['email']);
        $activity = Activity::query()->whereMorphedTo('subject', $customer)->latest('id')->firstOrFail();
        $this->assertSame('chantier@exemple.fr', $activity->attribute_changes?->get('attributes')['email'] ?? null);
        $this->assertSame($this->author->agencyId(), $activity->properties->get('author_agency_id'));
    }

    public function test_the_same_email_changes_nothing(): void
    {
        Event::fake([CustomerChanged::class]);
        $customer = Customer::factory()->create(['email' => 'chantier@exemple.fr']);
        $activityCount = Activity::query()->count();

        app(UpdateCustomer::class)->changeEmail($customer, 'chantier@exemple.fr', $this->author);

        Event::assertNotDispatched(CustomerChanged::class);
        $this->assertSame($activityCount, Activity::query()->count());
    }

    public function test_an_empty_or_invalid_email_is_refused(): void
    {
        Event::fake([CustomerChanged::class]);
        $customer = Customer::factory()->create(['email' => 'chantier@exemple.fr']);

        $this->assertRefused(InvalidCustomerEmailException::class, 'adresse e-mail', fn () => app(UpdateCustomer::class)->changeEmail($customer, '  ', $this->author));
        $this->assertRefused(InvalidCustomerEmailException::class, 'pas-une-adresse', fn () => app(UpdateCustomer::class)->changeEmail($customer, 'pas-une-adresse', $this->author));

        $this->assertSame('chantier@exemple.fr', $customer->fresh()?->email);
        Event::assertNotDispatched(CustomerChanged::class);
    }
}
