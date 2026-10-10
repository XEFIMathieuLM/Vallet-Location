<?php

namespace Functional\Portal\Tests\Feature\Staff;

use Carbon\CarbonImmutable;
use Functional\Fleet\Models\Agency;
use Functional\Fleet\Models\MachineCategory;
use Functional\Fleet\Tests\Concerns\AssertsRefusals;
use Functional\Portal\Access\PortalPermission;
use Functional\Portal\Actions\SetIndicativePrice;
use Functional\Portal\Enums\PortalHistoryEvent;
use Functional\Portal\Exceptions\InvalidIndicativePriceException;
use Functional\Portal\Livewire\Customer\Search;
use Functional\Portal\Livewire\Staff\IndicativePrices;
use Functional\Portal\Models\CategoryIndicativePrice;
use Functional\Portal\Models\ReservationRequest;
use Functional\Portal\Tests\Concerns\BuildsPortalFixtures;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Activitylog\Models\Activity;
use Tests\TestCase;

class IndicativePricesTest extends TestCase
{
    use AssertsRefusals, BuildsPortalFixtures, RefreshDatabase;

    private MachineCategory $aerialPlatforms;

    protected function setUp(): void
    {
        parent::setUp();
        $this->travelTo(CarbonImmutable::parse('2026-10-10 09:00', 'Europe/Paris'));
        $this->seedPermissions();
        $this->aerialPlatforms = MachineCategory::factory()->create(['name' => 'Nacelle']);
    }

    public function test_an_employee_sets_then_changes_the_indicative_price_of_a_category(): void
    {
        $priceManager = $this->userWithPermissions(PortalPermission::ManagePrices);
        $this->actingAs($priceManager);

        $this->get(route('portal.staff.prices'))->assertOk()->assertSee('Nacelle');
        Livewire::test(IndicativePrices::class)->set("amounts.{$this->aerialPlatforms->id}", '95')->call('save', $this->aerialPlatforms->id)->assertHasNoErrors();
        Livewire::test(IndicativePrices::class)->set("amounts.{$this->aerialPlatforms->id}", '95,50')->call('save', $this->aerialPlatforms->id)->assertHasNoErrors();

        $price = CategoryIndicativePrice::query()->sole();
        $this->assertSame(9550, $price->daily_price_cents);
        $this->assertSame($priceManager->getKey(), $price->updated_by);
        $this->assertSame($priceManager->agencyId(), $price->updated_agency_id);
        $history = Activity::query()->where('event', PortalHistoryEvent::IndicativePriceSet->value)->whereMorphedTo('subject', $this->aerialPlatforms)->orderBy('id')->get();
        $this->assertCount(2, $history);
        $this->assertSame([9500, 9550], [$history[1]->properties['previous_price_cents'], $history[1]->properties['new_price_cents']]);
        Livewire::test(IndicativePrices::class)->assertSee('95,50');
    }

    public function test_a_price_that_is_not_strictly_positive_is_refused(): void
    {
        $priceManager = $this->userWithPermissions(PortalPermission::ManagePrices);
        $this->actingAs($priceManager);

        foreach (['0', '-5', 'abc'] as $typedAmount) {
            Livewire::test(IndicativePrices::class)->set("amounts.{$this->aerialPlatforms->id}", $typedAmount)->call('save', $this->aerialPlatforms->id)->assertHasErrors('refusal');
        }
        $this->assertRefused(InvalidIndicativePriceException::class, 'strictement positif', fn () => app(SetIndicativePrice::class)->handle($this->aerialPlatforms, '0', $priceManager));

        $this->assertSame(0, CategoryIndicativePrice::query()->count());
    }

    public function test_removing_the_price_brings_back_price_on_request_for_customers(): void
    {
        $this->actingAs($this->userWithPermissions(PortalPermission::ManagePrices));
        CategoryIndicativePrice::factory()->for($this->aerialPlatforms, 'category')->create(['daily_price_cents' => 9500]);

        Livewire::test(IndicativePrices::class)->call('remove', $this->aerialPlatforms->id)->assertHasNoErrors();

        $this->assertSame(0, CategoryIndicativePrice::query()->count());
        $this->assertTrue(Activity::query()->where('event', PortalHistoryEvent::IndicativePriceRemoved->value)->exists());
        $rouen = Agency::factory()->create();
        $this->reservableMachine($this->aerialPlatforms, $rouen);
        $this->actingAs($this->customerAccount(), 'customer');
        Livewire::test(Search::class)
            ->set('categoryId', $this->aerialPlatforms->id)
            ->set('agencyId', $rouen->id)
            ->set('startDate', '2026-11-10')
            ->set('endDate', '2026-11-12')
            ->assertSee(__('portal::search.price_on_request'));
    }

    public function test_a_request_keeps_the_price_shown_when_it_was_sent(): void
    {
        $priceManager = $this->userWithPermissions(PortalPermission::ManagePrices);
        app(SetIndicativePrice::class)->handle($this->aerialPlatforms, '95', $priceManager);
        $reservationRequest = $this->pendingRequest($this->customerAccount(), $this->reservableMachine($this->aerialPlatforms), '2026-11-10', '2026-11-12', ['indicative_daily_price_cents' => 9500]);

        app(SetIndicativePrice::class)->handle($this->aerialPlatforms, '110', $priceManager);

        $this->assertSame(9500, ReservationRequest::query()->findOrFail($reservationRequest->id)->indicative_daily_price_cents);
    }

    public function test_an_employee_without_the_permission_cannot_manage_prices(): void
    {
        $this->actingAs($this->userWithoutPermission());

        $this->get(route('portal.staff.prices'))->assertForbidden();
        $this->get(route('dashboard'))->assertDontSee(route('portal.staff.prices'));
    }
}
