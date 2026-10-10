<?php

namespace Functional\Deposit\Tests\Concerns;

use Carbon\CarbonImmutable;
use Functional\Booking\Actions\DepartReservation;
use Functional\Booking\Models\Customer;
use Functional\Booking\Models\Reservation;
use Functional\Deposit\Actions\CollectDeposit;
use Functional\Deposit\Enums\PaymentMethod;
use Functional\Deposit\Models\Deposit;
use Functional\Fleet\Contracts\AgencyMember;
use Functional\Inspection\Enums\InspectionStep;
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
}
