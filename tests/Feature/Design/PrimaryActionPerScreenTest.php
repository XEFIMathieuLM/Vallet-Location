<?php

namespace Tests\Feature\Design;

use Carbon\CarbonImmutable;
use Functional\Booking\Enums\ReservationStatus;
use Functional\Booking\Extensions\ReservationDetailSections;
use Functional\Booking\Models\Customer;
use Functional\Booking\Models\Reservation;
use Functional\Fleet\Models\Machine;
use Functional\Fleet\Tests\Concerns\CreatesUsers;
use Functional\Portal\Models\ReservationRequest;
use Functional\Sales\Models\Sale;
use Functional\Sales\Models\SaleOffer;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\InspectsPrimaryActions;
use Tests\TestCase;

class PrimaryActionPerScreenTest extends TestCase
{
    use CreatesUsers, InspectsPrimaryActions, RefreshDatabase;

    private Authenticatable $employee;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seedPermissions();
        $this->travelTo(CarbonImmutable::parse('2026-10-10 08:00', 'Europe/Paris'));
        $this->employee = $this->employee();
    }

    public function test_every_reference_screen_shows_at_most_one_yellow_action_outside_its_dialogs(): void
    {
        $machine = Machine::factory()->create();
        $reservation = Reservation::factory()->for($machine)->for(Customer::factory()->individual())
            ->between(CarbonImmutable::today(), CarbonImmutable::today()->addDays(3))
            ->withStatus(ReservationStatus::Confirmed)->create();
        $sale = Sale::factory()->listed()->create();
        SaleOffer::factory()->count(2)->for($sale)->create();
        ReservationRequest::factory()->count(2)->create();

        $urls = [
            route('availability.index'), route('reservations.index'), route('reservations.create', ['machine' => $machine->id]),
            route('reservations.show', $reservation), route('planning.index'), route('inspection.damages'),
            route('billing.transmissions'), route('billing.exports'), route('deposit.pending.index'), route('deposit.rates.index'),
            route('sales.index'), route('sales.create'), route('sales.show', $sale),
            route('certification.machines'), route('certification.certificates'),
            route('accounts.key-accounts'), route('accounts.missing-purchase-orders'),
            route('portal.staff.requests'), route('portal.staff.prices'),
            route('machines.index'), route('users.index'), route('profile.edit'), route('security.edit'),
        ];

        foreach ($urls as $url) {
            $html = (string) $this->actingAs($this->employee)->withSession(['auth.password_confirmed_at' => time()])->get($url)->assertOk()->getContent();
            $this->assertLessThanOrEqual(1, count($this->primaryActionLabels($html)), $url.' : '.implode(', ', $this->primaryActionLabels($html)));
        }
    }

    public function test_the_reservation_detail_keeps_its_sections_in_their_registered_order(): void
    {
        $reservation = Reservation::factory()->withStatus(ReservationStatus::Confirmed)->create();

        $html = (string) $this->actingAs($this->employee)->get(route('reservations.show', $reservation))->assertOk()->getContent();

        preg_match_all('/&quot;name&quot;:&quot;([a-z0-9.\-]+)&quot;/', $html, $matches);
        $renderedSections = array_values(array_intersect($matches[1], app(ReservationDetailSections::class)->all()));

        $this->assertSame(array_values(array_intersect(app(ReservationDetailSections::class)->all(), $renderedSections)), $renderedSections);
        $this->assertNotEmpty($renderedSections);
    }
}
