<?php

namespace Functional\Booking\Tests\Feature;

use App\Models\User;
use Carbon\CarbonImmutable;
use Database\Seeders\PermissionSeeder;
use Functional\Booking\Livewire\CreateReservationForm;
use Functional\Booking\Models\Customer;
use Functional\Booking\Models\Reservation;
use Functional\Fleet\Models\Agency;
use Functional\Fleet\Models\Machine;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Features\SupportTesting\Testable;
use Livewire\Livewire;
use Tests\TestCase;

class CreateReservationFormTest extends TestCase
{
    use RefreshDatabase;

    private Machine $machine;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(PermissionSeeder::class);
        $this->travelTo(CarbonImmutable::parse('2026-11-01'));
        $this->machine = Machine::factory()->create();
    }

    public function test_the_form_is_prefilled_from_the_availability_search(): void
    {
        $this->actingAs(User::factory()->employee()->create())
            ->get(route('reservations.create', ['machine' => $this->machine->id, 'du' => '2026-11-10', 'au' => '2026-11-14']))
            ->assertOk()
            ->assertSee($this->machine->reference);
    }

    public function test_a_reservation_is_created_for_an_existing_customer(): void
    {
        $customer = Customer::factory()->create();

        $this->openForm('2026-11-10', '2026-11-14')
            ->set('customerId', $customer->id)
            ->call('save')
            ->assertHasNoErrors()
            ->assertRedirect(route('availability.index'));

        $this->assertTrue(Reservation::query()->where('customer_id', $customer->id)->exists());
    }

    public function test_a_new_customer_is_created_with_the_reservation(): void
    {
        $this->openForm('2026-11-10', '2026-11-14')
            ->set('isNewCustomer', true)
            ->set('newCustomerName', 'BTP Savoie')
            ->set('newCustomerEmail', 'contact@btp-savoie.test')
            ->call('save')
            ->assertHasNoErrors();

        $customer = Customer::query()->where('name', 'BTP Savoie')->firstOrFail();
        $this->assertTrue(Reservation::query()->where('customer_id', $customer->id)->exists());
    }

    public function test_a_new_customer_needs_a_phone_or_an_email(): void
    {
        $this->openForm('2026-11-10', '2026-11-14')
            ->set('isNewCustomer', true)
            ->set('newCustomerName', 'BTP Savoie')
            ->call('save')
            ->assertHasErrors(['newCustomerPhone', 'newCustomerEmail']);

        $this->assertSame(0, Customer::query()->count());
    }

    public function test_an_overlap_refusal_is_displayed_and_nothing_is_saved(): void
    {
        Reservation::factory()
            ->for($this->machine)
            ->for(Agency::factory()->create(['name' => 'Grenoble']))
            ->between(CarbonImmutable::parse('2026-11-10'), CarbonImmutable::parse('2026-11-14'))
            ->create();

        $this->openForm('2026-11-13', '2026-11-16')
            ->set('isNewCustomer', true)
            ->set('newCustomerName', 'BTP Savoie')
            ->set('newCustomerPhone', '0450000000')
            ->call('save')
            ->assertHasErrors('refusal')
            ->assertSee('par l&#039;agence Grenoble', escape: false);

        $this->assertSame(1, Reservation::query()->count());
        $this->assertFalse(Customer::query()->where('name', 'BTP Savoie')->exists());
    }

    private function openForm(string $startDate, string $endDate): Testable
    {
        return Livewire::actingAs(User::factory()->employee()->create())
            ->withQueryParams(['machine' => $this->machine->id, 'du' => $startDate, 'au' => $endDate])
            ->test(CreateReservationForm::class);
    }
}
