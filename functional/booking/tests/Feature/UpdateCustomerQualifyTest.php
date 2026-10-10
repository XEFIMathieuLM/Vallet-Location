<?php

namespace Functional\Booking\Tests\Feature;

use Functional\Booking\Actions\UpdateCustomer;
use Functional\Booking\Enums\CustomerType;
use Functional\Booking\Events\CustomerChanged;
use Functional\Booking\Extensions\CustomerChangeGuards;
use Functional\Booking\Models\Customer;
use Functional\Booking\Tests\Concerns\WithoutTransitionExtensions;
use Functional\Booking\Tests\Doubles\GuardRefusalException;
use Functional\Booking\Tests\Doubles\RecordingCustomerChangeGuard;
use Functional\Booking\Tests\Doubles\RefusingCustomerChangeGuard;
use Functional\Fleet\Contracts\AgencyMember;
use Functional\Fleet\Tests\Concerns\AssertsRefusals;
use Functional\Fleet\Tests\Concerns\CreatesUsers;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Spatie\Activitylog\Models\Activity;
use Tests\TestCase;

class UpdateCustomerQualifyTest extends TestCase
{
    use AssertsRefusals, CreatesUsers, RefreshDatabase, WithoutTransitionExtensions;

    private Model&Authenticatable&AgencyMember $author;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seedPermissions();
        $this->author = $this->employee();
        $this->actingAs($this->author);
        RecordingCustomerChangeGuard::$calls = [];
    }

    public function test_an_untyped_customer_is_qualified_and_the_change_is_announced_and_logged(): void
    {
        Event::fake([CustomerChanged::class]);
        $customer = Customer::factory()->untyped()->create();

        app(UpdateCustomer::class)->qualify($customer, CustomerType::Individual, $this->author);

        $this->assertSame(CustomerType::Individual, $customer->fresh()?->type);
        Event::assertDispatched(CustomerChanged::class, fn (CustomerChanged $event): bool => $event->customer->is($customer) && $event->changedAttributes === ['type']);
        $activity = $this->lastActivityOf($customer);
        $this->assertSame('updated', $activity->event);
        $this->assertNull($activity->attribute_changes?->get('old')['type'] ?? null);
        $this->assertSame('individual', $activity->attribute_changes?->get('attributes')['type'] ?? null);
        $this->assertSame($this->author->agencyId(), $activity->properties->get('author_agency_id'));
    }

    public function test_the_given_author_is_recorded_even_when_another_employee_is_logged_in(): void
    {
        $customer = Customer::factory()->untyped()->create();
        $otherAuthor = $this->employee();

        app(UpdateCustomer::class)->qualify($customer, CustomerType::Professional, $otherAuthor);

        $this->assertTrue($this->lastActivityOf($customer)->causer?->is($otherAuthor));
    }

    public function test_qualifying_with_the_same_type_changes_nothing(): void
    {
        Event::fake([CustomerChanged::class]);
        $customer = Customer::factory()->professional()->create();
        $activityCount = Activity::query()->count();

        app(UpdateCustomer::class)->qualify($customer, CustomerType::Professional, $this->author);

        Event::assertNotDispatched(CustomerChanged::class);
        $this->assertSame($activityCount, Activity::query()->count());
    }

    public function test_a_refusing_guard_leaves_the_type_unchanged(): void
    {
        Event::fake([CustomerChanged::class]);
        app(CustomerChangeGuards::class)->register(RefusingCustomerChangeGuard::class);
        $customer = Customer::factory()->professional()->create();

        $this->assertRefused(GuardRefusalException::class, 'Requalification refusée.', fn () => app(UpdateCustomer::class)->qualify($customer, CustomerType::Individual, $this->author));

        $this->assertSame(CustomerType::Professional, $customer->fresh()?->type);
        Event::assertNotDispatched(CustomerChanged::class);
    }

    public function test_guards_receive_the_current_type_and_the_new_type(): void
    {
        app(CustomerChangeGuards::class)->register(RecordingCustomerChangeGuard::class);
        $customer = Customer::factory()->untyped()->create();

        app(UpdateCustomer::class)->qualify($customer, CustomerType::Professional, $this->author);

        $this->assertSame([['previous' => null, 'next' => CustomerType::Professional]], RecordingCustomerChangeGuard::$calls);
    }

    public function test_booking_registers_no_guard_of_its_own(): void
    {
        $this->refreshApplication();

        $bookingGuards = array_filter(
            app(CustomerChangeGuards::class)->all(),
            fn (object $guard): bool => str_starts_with($guard::class, 'Functional\\Booking\\'),
        );

        $this->assertSame([], $bookingGuards);
    }

    private function lastActivityOf(Customer $customer): Activity
    {
        return Activity::query()->whereMorphedTo('subject', $customer)->latest('id')->firstOrFail();
    }
}
