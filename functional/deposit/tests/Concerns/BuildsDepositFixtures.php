<?php

namespace Functional\Deposit\Tests\Concerns;

use Carbon\CarbonImmutable;
use Functional\Billing\Models\DamageSettlement;
use Functional\Billing\Money\Money;
use Functional\Booking\Actions\DepartReservation;
use Functional\Booking\Actions\ReturnReservation;
use Functional\Booking\Enums\ReturnCondition;
use Functional\Booking\Models\Customer;
use Functional\Booking\Models\Reservation;
use Functional\Deposit\Actions\CollectDeposit;
use Functional\Deposit\Enums\PaymentMethod;
use Functional\Deposit\Models\Deposit;
use Functional\Fleet\Contracts\AgencyMember;
use Functional\Inspection\Enums\InspectionStep;
use Functional\Inspection\Models\Damage;
use Functional\Inspection\Models\ReservationView;
use Functional\Inspection\Tests\Concerns\BuildsPhotoSessions;
use Illuminate\Database\Eloquent\Model;

trait BuildsDepositFixtures
{
    use BuildsPhotoSessions;

    protected function confirmedReservationFor(Customer $customer): Reservation
    {
        return Reservation::factory()
            ->for($customer)
            ->between(CarbonImmutable::today(), CarbonImmutable::today()->addDays(3))
            ->create();
    }

    protected function collect(Reservation $reservation, Model&AgencyMember $author, PaymentMethod $method = PaymentMethod::Cheque, ?string $reference = '1234567'): Deposit
    {
        return app(CollectDeposit::class)->handle($reservation, $method, $reference, $author);
    }

    protected function depart(Reservation $reservation): Reservation
    {
        $this->photographEveryView($reservation, InspectionStep::Departure);

        return app(DepartReservation::class)->handle($reservation);
    }

    protected function returnMachine(Reservation $reservation): Reservation
    {
        $this->photographEveryView($reservation, InspectionStep::Return);

        return app(ReturnReservation::class)->handle($reservation, ReturnCondition::GoodState);
    }

    protected function closedReservationWithDeposit(Model&AgencyMember $author, int $amountCents = 150000): Deposit
    {
        $reservation = $this->confirmedReservationFor(Customer::factory()->individual()->create());
        $deposit = $this->collect($reservation, $author);
        $deposit->update(['amount' => Money::fromStored($amountCents)]);
        $this->depart($reservation);
        $this->returnMachine($reservation);

        return $deposit->refresh();
    }

    protected function unresolvedDamageOn(Reservation $reservation): Damage
    {
        return Damage::factory()->create([
            'reservation_view_id' => ReservationView::query()->whereBelongsTo($reservation)->value('id'),
        ]);
    }

    protected function billedDamageOn(Reservation $reservation, int $amountCents): DamageSettlement
    {
        return DamageSettlement::factory()
            ->billed(Money::fromStored($amountCents), 'Réparation')
            ->for(Damage::factory()->resolved()->state(['reservation_view_id' => ReservationView::query()->whereBelongsTo($reservation)->value('id')]))
            ->create();
    }

    protected function waivedDamageOn(Reservation $reservation): DamageSettlement
    {
        return DamageSettlement::factory()
            ->waived('Usure normale')
            ->for(Damage::factory()->resolved()->state(['reservation_view_id' => ReservationView::query()->whereBelongsTo($reservation)->value('id')]))
            ->create();
    }
}
