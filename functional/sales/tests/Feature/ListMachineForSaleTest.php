<?php

namespace Functional\Sales\Tests\Feature;

use Carbon\CarbonImmutable;
use Functional\Billing\Money\Money;
use Functional\Fleet\Enums\MachineStatus;
use Functional\Fleet\Models\Machine;
use Functional\Sales\Actions\ListMachineForSale;
use Functional\Sales\Actions\UpdateSaleListing;
use Functional\Sales\Data\SaleListing;
use Functional\Sales\Enums\SaleStatus;
use Functional\Sales\Events\SaleChanged;
use Functional\Sales\Exceptions\InvalidSaleListingException;
use Functional\Sales\Exceptions\MachineAlreadyForSaleException;
use Functional\Sales\Exceptions\MachineAlreadySoldException;
use Functional\Sales\Exceptions\SaleListingLockedException;
use Functional\Sales\Models\Sale;
use Functional\Sales\Tests\Concerns\BuildsSalesFixtures;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Spatie\Activitylog\Models\Activity;
use Tests\TestCase;

class ListMachineForSaleTest extends TestCase
{
    use BuildsSalesFixtures, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->travelTo(CarbonImmutable::parse('2026-11-02 10:00'));
    }

    public function test_a_machine_put_up_for_sale_opens_a_listed_sale_visible_with_its_price_and_agency(): void
    {
        Event::fake([SaleChanged::class]);
        $author = $this->employee();
        $machine = $this->machineForSale();

        $sale = $this->list($author, $machine, '18000');

        $this->assertSame(SaleStatus::Listed, $sale->status);
        $this->assertTrue(Money::fromStored(1800000)->equals($sale->asking_price));
        $this->assertSame($author->agencyId(), $sale->agency_id);
        $this->assertSame($author->getKey(), $sale->listed_by);
        $this->assertSame('Bon état général', $sale->condition);
        $this->assertSame(2016, $sale->year_of_manufacture);
        $this->assertSame(4200, $sale->operating_hours);
        Event::assertDispatched(SaleChanged::class, fn (SaleChanged $event): bool => $event->sale->is($sale));
        $activity = Activity::query()->where('log_name', 'sales')->where('event', 'listed')->sole();
        $this->assertSame($author->agencyId(), $activity->properties['author_agency_id']);
    }

    public function test_a_machine_already_for_sale_cannot_be_listed_again_and_the_open_sale_is_shown(): void
    {
        $openSale = $this->listedSale($this->machineForSale(), 18000);

        $this->assertRefused(
            MachineAlreadyForSaleException::class,
            '18000,00 €',
            fn () => $this->list($this->employee(), $openSale->machine, '15000'),
        );
        $this->assertSame(1, Sale::query()->count());
    }

    public function test_the_asking_price_can_change_while_listed_and_the_old_price_stays_in_the_history(): void
    {
        $author = $this->employee();
        $sale = $this->listedSale(askingPriceInEuros: 18000);

        app(UpdateSaleListing::class)->handle($author, $sale, $this->listing('17500'));

        $this->assertTrue(Money::fromStored(1750000)->equals($sale->fresh()?->asking_price));
        $activity = Activity::query()->where('log_name', 'sales')->where('event', 'asking_price_changed')->sole();
        $this->assertSame('18000,00', $activity->properties['old_price']);
        $this->assertSame('17500,00', $activity->properties['new_price']);
    }

    public function test_the_asking_price_of_a_reserved_sale_is_locked_but_its_description_is_not(): void
    {
        $author = $this->employee();
        $sale = $this->reservedSale('2026-11-20');

        $this->assertRefused(SaleListingLockedException::class, 'Réservée', fn () => app(UpdateSaleListing::class)->handle($author, $sale, $this->listing('1')));

        $newDescription = new SaleListing($sale->asking_price, 2016, 4300, 'Révision faite', 'Pneus neufs');
        app(UpdateSaleListing::class)->handle($author, $sale, $newDescription);
        $this->assertSame('Révision faite', $sale->fresh()?->condition);
    }

    public function test_a_machine_whose_sale_was_concluded_can_never_be_listed_again(): void
    {
        $soldSale = Sale::factory()->sold()->create();

        $this->assertRefused(MachineAlreadySoldException::class, 'vendue', fn () => $this->list($this->employee(), $soldSale->machine, '12000'));
    }

    public function test_a_broken_workshop_or_retired_machine_can_be_listed_and_a_zero_price_is_refused(): void
    {
        $author = $this->employee();

        foreach ([MachineStatus::OutOfOrder, MachineStatus::Workshop, MachineStatus::Retired] as $status) {
            $machine = Machine::factory()->withStatus($status)->create();
            $this->assertSame(SaleStatus::Listed, $this->list($author, $machine, '9000')->status);
        }

        $this->assertRefused(InvalidSaleListingException::class, 'prix', fn () => $this->list($author, $this->machineForSale('MP-0001'), '0'));
    }

    private function list(mixed $author, Machine $machine, string $askingPrice): Sale
    {
        return app(ListMachineForSale::class)->handle($author, $machine, $this->listing($askingPrice));
    }

    private function listing(string $askingPrice): SaleListing
    {
        return new SaleListing(Money::fromInput($askingPrice), 2016, 4200, 'Bon état général', null);
    }
}
