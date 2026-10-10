<?php

namespace Functional\Sales\Tests\Feature;

use Carbon\CarbonImmutable;
use Functional\Billing\Money\Money;
use Functional\Sales\Actions\AcceptOffer;
use Functional\Sales\Actions\CancelSale;
use Functional\Sales\Actions\HandOverSale;
use Functional\Sales\Actions\ListMachineForSale;
use Functional\Sales\Data\SaleListing;
use Functional\Sales\Events\SaleChanged;
use Functional\Sales\Models\SaleOffer;
use Functional\Sales\Tests\Concerns\BuildsSalesFixtures;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class SaleBroadcastingTest extends TestCase
{
    use BuildsSalesFixtures, RefreshDatabase;

    public function test_opening_reserving_concluding_and_cancelling_a_sale_are_broadcast_to_every_agency(): void
    {
        $this->travelTo(CarbonImmutable::parse('2026-11-02 10:00'));
        Event::fake([SaleChanged::class]);
        $author = $this->employee();

        $sale = app(ListMachineForSale::class)->handle($author, $this->machineForSale(), new SaleListing(Money::fromInput('18000'), null, null, 'Bon état', null));
        app(AcceptOffer::class)->handle($author, SaleOffer::factory()->create(['sale_id' => $sale->id]), CarbonImmutable::today());
        app(HandOverSale::class)->handle($author, $sale);
        app(CancelSale::class)->handle($author, $this->listedSale($this->machineForSale('MP-0001')), 'Machine conservée');

        $statuses = Event::dispatched(SaleChanged::class)->map(fn (array $arguments): string => $arguments[0]->broadcastWith()['status'])->all();
        $this->assertSame(['listed', 'reserved', 'sold', 'cancelled'], $statuses);
    }

    public function test_the_event_goes_after_commit_on_the_private_sales_channel_with_an_explicit_payload(): void
    {
        $sale = $this->reservedSale('2026-12-20');
        $event = new SaleChanged($sale);

        $this->assertEquals([new PrivateChannel('sales')], $event->broadcastOn());
        $this->assertSame('sale.changed', $event->broadcastAs());
        $this->assertSame(['id' => $sale->id, 'machine_id' => $sale->machine_id, 'status' => 'reserved', 'planned_handover_date' => '2026-12-20'], $event->broadcastWith());
        $this->assertInstanceOf(ShouldDispatchAfterCommit::class, $event);
    }
}
