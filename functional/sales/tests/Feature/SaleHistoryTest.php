<?php

namespace Functional\Sales\Tests\Feature;

use Carbon\CarbonImmutable;
use Functional\Billing\Money\Money;
use Functional\Booking\Models\Customer;
use Functional\Sales\Actions\AcceptOffer;
use Functional\Sales\Actions\CancelSale;
use Functional\Sales\Actions\ChangePlannedHandoverDate;
use Functional\Sales\Actions\HandOverSale;
use Functional\Sales\Actions\ListMachineForSale;
use Functional\Sales\Actions\RecordOffer;
use Functional\Sales\Actions\RejectOffer;
use Functional\Sales\Actions\ReleaseSaleReservation;
use Functional\Sales\Actions\UpdateSaleListing;
use Functional\Sales\Actions\WithdrawOffer;
use Functional\Sales\Data\SaleListing;
use Functional\Sales\Livewire\MachineSaleHistory;
use Functional\Sales\Tests\Concerns\BuildsSalesFixtures;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Livewire\Livewire;
use Spatie\Activitylog\Models\Activity;
use Tests\TestCase;

class SaleHistoryTest extends TestCase
{
    use BuildsSalesFixtures, RefreshDatabase;

    public function test_every_action_on_a_sale_is_traced_with_its_author_agency_and_date(): void
    {
        Bus::fake();
        $this->travelTo(CarbonImmutable::parse('2026-11-02 10:00'));
        $author = $this->employee();
        $machine = $this->machineForSale();
        $listing = new SaleListing(Money::fromInput('18000'), null, null, 'Bon état', null);

        $firstSale = app(ListMachineForSale::class)->handle($author, $machine, $listing);
        app(UpdateSaleListing::class)->handle($author, $firstSale, new SaleListing(Money::fromInput('17000'), null, null, 'Bon état', null));
        $rejected = app(RecordOffer::class)->handle($author, $firstSale, Customer::factory()->create(), Money::fromInput('10000'), CarbonImmutable::today());
        $withdrawn = app(RecordOffer::class)->handle($author, $firstSale, Customer::factory()->create(), Money::fromInput('11000'), CarbonImmutable::today());
        $accepted = app(RecordOffer::class)->handle($author, $firstSale, Customer::factory()->create(), Money::fromInput('16000'), CarbonImmutable::today());
        app(RejectOffer::class)->handle($author, $rejected);
        app(WithdrawOffer::class)->handle($author, $withdrawn);
        app(AcceptOffer::class)->handle($author, $accepted, CarbonImmutable::parse('2026-11-20'));
        app(ChangePlannedHandoverDate::class)->handle($author, $firstSale, CarbonImmutable::parse('2026-11-21'));
        app(ReleaseSaleReservation::class)->handle($author, $firstSale, 'Financement refusé');
        app(CancelSale::class)->handle($author, $firstSale, 'Machine conservée');
        $secondSale = app(ListMachineForSale::class)->handle($author, $machine, $listing);
        $buyerOffer = app(RecordOffer::class)->handle($author, $secondSale, Customer::factory()->create(), Money::fromInput('15000'), CarbonImmutable::today());
        app(AcceptOffer::class)->handle($author, $buyerOffer, CarbonImmutable::today());
        app(HandOverSale::class)->handle($author, $secondSale);

        $events = Activity::query()->where('log_name', 'sales')->orderBy('id')->pluck('event')->all();
        $this->assertSame([
            'listed', 'asking_price_changed', 'offer_recorded', 'offer_recorded', 'offer_recorded', 'offer_rejected', 'offer_withdrawn',
            'offer_accepted', 'handover_date_changed', 'reservation_released', 'cancelled', 'listed', 'offer_recorded', 'offer_accepted', 'handed_over',
        ], $events);
        Activity::query()->where('log_name', 'sales')->each(function (Activity $activity) use ($author): void {
            $this->assertSame($author->getKey(), $activity->causer_id);
            $this->assertSame($author->agencyId(), $activity->properties['author_agency_id']);
            $this->assertNotNull($activity->created_at);
        });

        Livewire::actingAs($author)->test(MachineSaleHistory::class, ['machine' => $machine])
            ->assertSee('Annulée')->assertSee('Vendue')->assertSee('Machine conservée')->assertSee('16000,00');
        $this->actingAs($author)->get(route('sales.machine-history', $machine))->assertOk();
    }
}
