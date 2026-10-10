<?php

namespace Tests\Feature\Portal;

use Carbon\CarbonImmutable;
use Functional\Accounts\Models\KeyAccount;
use Functional\Billing\Gateways\FakeBillingGateway;
use Functional\Billing\Models\CustomerBillingAccount;
use Functional\Booking\Enums\ReservationStatus;
use Functional\Booking\Events\ReservationChanged;
use Functional\Booking\Models\Reservation;
use Functional\Fleet\Enums\MachineStatus;
use Functional\Fleet\Models\Agency;
use Functional\Fleet\Models\MachineCategory;
use Functional\Portal\Actions\ConfirmReservationRequest;
use Functional\Portal\Data\CustomerChoice;
use Functional\Portal\Livewire\Customer\Search;
use Functional\Portal\Models\CategoryIndicativePrice;
use Functional\Portal\Tests\Concerns\BuildsPortalFixtures;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;
use Tests\TestCase;

class IndicativePriceIsolationTest extends TestCase
{
    use BuildsPortalFixtures, RefreshDatabase;

    private MachineCategory $aerialPlatforms;

    protected function setUp(): void
    {
        parent::setUp();
        $this->travelTo(CarbonImmutable::parse('2026-10-10 09:00', 'Europe/Paris'));
        $this->aerialPlatforms = MachineCategory::factory()->create();
        CategoryIndicativePrice::factory()->for($this->aerialPlatforms, 'category')->create(['daily_price_cents' => 9500]);
    }

    public function test_a_key_account_sees_the_same_indicative_price_as_any_customer(): void
    {
        $rouen = Agency::factory()->create();
        $this->reservableMachine($this->aerialPlatforms, $rouen);
        $keyAccount = KeyAccount::factory()->create();

        foreach ([$this->attachedCustomerAccount($keyAccount->customer), $this->customerAccount()] as $account) {
            $this->actingAs($account, 'customer');
            Livewire::test(Search::class)
                ->set('categoryId', $this->aerialPlatforms->id)
                ->set('agencyId', $rouen->id)
                ->set('startDate', '2026-11-10')
                ->set('endDate', '2026-11-12')
                ->assertSee('à partir de 95,00', false);
        }
    }

    public function test_the_indicative_price_is_never_transmitted_to_the_billing_software(): void
    {
        Notification::fake();
        config(['billing.go_live_date' => '2026-01-01', 'billing.gateway' => 'fake']);
        app(FakeBillingGateway::class)->reset();
        $this->seedPermissions();
        $machine = $this->reservableMachine($this->aerialPlatforms);
        $reservationRequest = $this->pendingRequest($this->customerAccount(), $machine, '2026-11-10', '2026-11-14', ['indicative_daily_price_cents' => 9500]);
        $confirmed = app(ConfirmReservationRequest::class)->handle($reservationRequest, $machine, CustomerChoice::create(), $this->requestHandler());
        $reservation = Reservation::query()->findOrFail($confirmed->reservation_id);
        CustomerBillingAccount::factory()->create(['customer_id' => $reservation->customer_id]);

        $this->travelTo(CarbonImmutable::parse('2026-11-14 17:00', 'Europe/Paris'));
        $machine->update(['status' => MachineStatus::RentedOut]);
        $reservation->update(['status' => ReservationStatus::Closed, 'departed_at' => CarbonImmutable::parse('2026-11-10 08:00'), 'returned_at' => CarbonImmutable::now()]);
        ReservationChanged::dispatch($reservation->refresh());

        $transmittedLines = app(FakeBillingGateway::class)->received();
        $this->assertCount(1, $transmittedLines);
        $transmittedLine = array_values($transmittedLines)[0];
        $this->assertNotContains(9500, $transmittedLine);
        $this->assertNotContains('95', $transmittedLine);
        $this->assertSame([], array_filter(array_keys($transmittedLine), fn (string $field): bool => str_contains($field, 'price')));
        $this->assertNull($transmittedLine['amount_excl_tax_cents']);
    }
}
