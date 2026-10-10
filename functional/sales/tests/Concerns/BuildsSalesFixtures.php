<?php

namespace Functional\Sales\Tests\Concerns;

use Carbon\CarbonImmutable;
use Functional\Billing\Gateways\FakeBillingGateway;
use Functional\Billing\Money\Money;
use Functional\Booking\Enums\ReservationStatus;
use Functional\Booking\Models\Reservation;
use Functional\Fleet\Contracts\AgencyMember;
use Functional\Fleet\Models\Machine;
use Functional\Fleet\Tests\Concerns\AssertsRefusals;
use Functional\Fleet\Tests\Concerns\CreatesUsers;
use Functional\Sales\Models\Sale;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;

trait BuildsSalesFixtures
{
    use AssertsRefusals;
    use CreatesUsers {
        employee as protected createEmployee;
    }

    protected function setUpBuildsSalesFixtures(): void
    {
        config(['billing.go_live_date' => '2026-01-01', 'billing.gateway' => 'fake']);
        $this->fakeGateway()->reset();
    }

    protected function employee(): Model&Authenticatable&AgencyMember
    {
        $this->seedPermissions();

        return $this->createEmployee();
    }

    protected function fakeGateway(): FakeBillingGateway
    {
        return app(FakeBillingGateway::class);
    }

    protected function machineForSale(string $reference = 'NAC-0042'): Machine
    {
        return Machine::factory()->create(['reference' => $reference]);
    }

    protected function listedSale(?Machine $machine = null, int $askingPriceInEuros = 18000): Sale
    {
        return Sale::factory()->listed()->create([
            'machine_id' => ($machine ?? $this->machineForSale())->id,
            'asking_price' => Money::fromStored($askingPriceInEuros * 100),
        ]);
    }

    protected function reservedSale(string $plannedHandoverDate, ?Machine $machine = null): Sale
    {
        return Sale::factory()->reserved(CarbonImmutable::parse($plannedHandoverDate))->create([
            'machine_id' => ($machine ?? $this->machineForSale())->id,
        ])->refresh();
    }

    protected function confirmedReservation(Machine $machine, string $startDate, string $endDate, ReservationStatus $status = ReservationStatus::Confirmed): Reservation
    {
        return Reservation::factory()
            ->between(CarbonImmutable::parse($startDate), CarbonImmutable::parse($endDate))
            ->withStatus($status)
            ->create(['machine_id' => $machine->id]);
    }
}
