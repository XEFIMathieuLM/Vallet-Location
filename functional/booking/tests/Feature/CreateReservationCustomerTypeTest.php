<?php

namespace Functional\Booking\Tests\Feature;

use Carbon\CarbonImmutable;
use Functional\Booking\Actions\CreateReservation;
use Functional\Booking\Data\NewCustomer;
use Functional\Booking\Enums\CustomerType;
use Functional\Booking\Livewire\CreateReservationForm;
use Functional\Booking\Models\Customer;
use Functional\Booking\Models\Reservation;
use Functional\Fleet\Models\Machine;
use Functional\Fleet\Tests\Concerns\CreatesUsers;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Features\SupportTesting\Testable;
use Livewire\Livewire;
use Tests\TestCase;

class CreateReservationCustomerTypeTest extends TestCase
{
    use CreatesUsers, RefreshDatabase;

    private Machine $machine;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seedPermissions();
        $this->travelTo(CarbonImmutable::parse('2026-11-01'));
        $this->machine = Machine::factory()->create();
    }

    public function test_a_new_customer_without_type_is_refused(): void
    {
        $this->newCustomerForm()
            ->call('save')
            ->assertHasErrors(['newCustomerType']);

        $this->assertSame(0, Reservation::query()->count());
    }

    public function test_a_new_customer_is_created_with_the_chosen_type(): void
    {
        $this->newCustomerForm()
            ->set('newCustomerType', 'individual')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertSame(CustomerType::Individual, Customer::query()->where('name', 'Jean Martin')->sole()->type);
    }

    public function test_an_existing_untyped_customer_can_still_book_and_stays_untyped(): void
    {
        $customer = Customer::factory()->untyped()->create();

        Livewire::actingAs($this->employee())
            ->test(CreateReservationForm::class, ['machineId' => $this->machine->id, 'startDate' => '2026-11-10', 'endDate' => '2026-11-14'])
            ->set('customerId', $customer->id)
            ->call('save')
            ->assertHasNoErrors();

        $this->assertNull($customer->fresh()?->type);
        $this->assertSame(1, Reservation::query()->whereBelongsTo($customer)->count());
    }

    public function test_the_action_persists_the_type_of_a_new_customer(): void
    {
        $reservation = app(CreateReservation::class)->handle(
            $this->employee(),
            $this->machine,
            new NewCustomer(name: 'Lucie Bernard', phone: '0450000000', email: null, type: CustomerType::Individual),
            CarbonImmutable::parse('2026-11-10'),
            CarbonImmutable::parse('2026-11-12'),
        );

        $this->assertSame(CustomerType::Individual, $reservation->customer->type);
    }

    private function newCustomerForm(): Testable
    {
        return Livewire::actingAs($this->employee())
            ->test(CreateReservationForm::class, ['machineId' => $this->machine->id, 'startDate' => '2026-11-10', 'endDate' => '2026-11-14'])
            ->set('isNewCustomer', true)
            ->set('newCustomerName', 'Jean Martin')
            ->set('newCustomerPhone', '0450000000');
    }
}
