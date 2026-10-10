<?php

namespace Functional\Billing\Tests\Concerns;

use Carbon\CarbonImmutable;
use Functional\Billing\Gateways\FakeBillingGateway;
use Functional\Billing\Models\CustomerBillingAccount;
use Functional\Booking\Enums\ReservationStatus;
use Functional\Booking\Models\Reservation;
use Functional\Fleet\Contracts\AgencyMember;
use Functional\Fleet\Enums\MachineStatus;
use Functional\Fleet\Tests\Concerns\CreatesUsers;
use Functional\Inspection\Models\Damage;
use Functional\Inspection\Models\ReservationView;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;

trait BuildsBillingFixtures
{
    use CreatesUsers {
        employee as protected createEmployee;
    }

    protected function setUpBuildsBillingFixtures(): void
    {
        config(['billing.go_live_date' => '2026-01-01', 'billing.gateway' => 'fake']);
        $this->fakeGateway()->reset();
    }

    protected function fakeGateway(): FakeBillingGateway
    {
        return app(FakeBillingGateway::class);
    }

    protected function employee(): Model&Authenticatable&AgencyMember
    {
        $this->seedPermissions();

        return $this->createEmployee();
    }

    protected function inProgressReservation(string $departedAt, bool $hasBillingAccount = true): Reservation
    {
        $departure = CarbonImmutable::parse($departedAt)->setTimezone(config()->string('app.timezone'));
        $reservation = Reservation::factory()
            ->between($departure->startOfDay(), $departure->startOfDay()->addDays(4))
            ->withStatus(ReservationStatus::InProgress)
            ->create(['departed_at' => $departure]);
        $reservation->machine->update(['status' => MachineStatus::RentedOut]);

        if ($hasBillingAccount) {
            CustomerBillingAccount::factory()->create(['customer_id' => $reservation->customer_id]);
        }

        return $reservation;
    }

    protected function closedReservation(string $departedAt, string $returnedAt, bool $hasBillingAccount = true): Reservation
    {
        $reservation = $this->inProgressReservation($departedAt, $hasBillingAccount);
        $reservation->update(['status' => ReservationStatus::Closed, 'returned_at' => CarbonImmutable::parse($returnedAt)->setTimezone(config()->string('app.timezone'))]);

        return $reservation->refresh();
    }

    protected function unresolvedDamage(Reservation $reservation, string $viewLabel = 'Gauche', string $comment = 'Bras rayé'): Damage
    {
        $view = ReservationView::factory()->create(['reservation_id' => $reservation->id, 'label' => $viewLabel]);

        return Damage::factory()->create(['reservation_id' => $reservation->id, 'reservation_view_id' => $view->id, 'comment' => $comment]);
    }
}
