<?php

namespace Functional\Deposit\Tests\Feature;

use Functional\Billing\Money\Money;
use Functional\Booking\Access\BookingPermission;
use Functional\Booking\Models\Customer;
use Functional\Deposit\Actions\RemoveDepositRate;
use Functional\Deposit\Actions\ResolveDepositAmount;
use Functional\Deposit\Actions\SetDepositRate;
use Functional\Deposit\Exceptions\DepositRefusedException;
use Functional\Deposit\Livewire\DepositRateForm;
use Functional\Deposit\Livewire\DepositRatesIndex;
use Functional\Deposit\Models\DepositRate;
use Functional\Deposit\Tests\Concerns\BuildsDepositFixtures;
use Functional\Fleet\Contracts\AgencyMember;
use Functional\Fleet\Models\MachineCategory;
use Functional\Fleet\Tests\Concerns\AssertsRefusals;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class DepositRatesTest extends TestCase
{
    use AssertsRefusals, BuildsDepositFixtures, RefreshDatabase;

    private Model&Authenticatable&AgencyMember $author;

    protected function setUp(): void
    {
        parent::setUp();

        $this->author = $this->seededEmployee();
        $this->actingAs($this->author);
    }

    public function test_a_new_category_amount_applies_to_new_deposits_only(): void
    {
        $reservation = $this->confirmedReservationFor(Customer::factory()->individual()->create());
        $collected = $this->collect($reservation, $this->author);
        $category = $reservation->machine->category;

        app(SetDepositRate::class)->handle($category, Money::fromStored(300000), $this->author);

        $this->assertSame(300000, app(ResolveDepositAmount::class)->forCategory($category)->minorUnits);
        $this->assertSame(150000, $collected->fresh()?->amount->minorUnits);
        $this->assertSame($this->author->getKey(), DepositRate::query()->where('machine_category_id', $category->id)->sole()->updated_by);
    }

    public function test_the_default_amount_can_be_changed_without_touching_collected_deposits(): void
    {
        $collected = $this->collect($this->confirmedReservationFor(Customer::factory()->individual()->create()), $this->author);

        app(SetDepositRate::class)->handle(null, Money::fromStored(200000), $this->author);

        $this->assertSame(200000, app(ResolveDepositAmount::class)->forCategory(MachineCategory::factory()->create())->minorUnits);
        $this->assertSame(1, DepositRate::query()->whereNull('machine_category_id')->count());
        $this->assertSame(150000, $collected->fresh()?->amount->minorUnits);
    }

    public function test_a_null_amount_is_refused(): void
    {
        $this->assertRefused(DepositRefusedException::class, 'strictement positif', fn () => app(SetDepositRate::class)->handle(null, Money::zero(), $this->author));
    }

    public function test_an_empty_or_malformed_amount_is_refused_by_the_form(): void
    {
        $category = MachineCategory::factory()->create();

        foreach (['', '-10', 'abc', '0'] as $typedAmount) {
            Livewire::test(DepositRateForm::class, ['category' => $category])
                ->set('amount', $typedAmount)
                ->call('save')
                ->assertHasErrors('amount');
        }

        $this->assertSame(0, DepositRate::query()->whereBelongsTo($category, 'category')->count());
    }

    public function test_removing_a_category_amount_falls_back_to_the_default(): void
    {
        $category = MachineCategory::factory()->create();
        DepositRate::factory()->forCategory($category)->create(['amount' => Money::fromStored(300000)]);

        app(RemoveDepositRate::class)->handle($category);

        $this->assertSame(150000, app(ResolveDepositAmount::class)->forCategory($category)->minorUnits);
    }

    public function test_the_screens_show_effective_amounts_and_save_them(): void
    {
        $category = MachineCategory::factory()->create(['name' => 'Nacelles']);

        Livewire::test(DepositRatesIndex::class)
            ->assertSee('Nacelles')
            ->assertSee('1500,00 €')
            ->set('defaultAmount', '1800')
            ->call('saveDefault')
            ->assertHasNoErrors();

        Livewire::test(DepositRateForm::class, ['category' => $category])
            ->set('amount', '3000')
            ->call('save')
            ->assertHasNoErrors()
            ->call('remove');

        $this->assertSame(180000, app(ResolveDepositAmount::class)->forCategory($category)->minorUnits);
    }

    public function test_the_screens_require_the_rates_permission(): void
    {
        $this->actingAs($this->userWithPermissions(BookingPermission::ManageReservations))
            ->get(route('deposit.rates.index'))
            ->assertForbidden();

        $this->actingAs($this->author)->get(route('deposit.rates.index'))->assertOk();
    }
}
