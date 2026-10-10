<?php

namespace Functional\Certification\Tests\Concerns;

use Carbon\CarbonImmutable;
use Functional\Booking\Actions\CreateReservation;
use Functional\Booking\Models\Customer;
use Functional\Booking\Models\Reservation;
use Functional\Certification\Models\ReservationCertificate;
use Functional\Certification\Models\VgpReport;
use Functional\Fleet\Contracts\AgencyMember;
use Functional\Fleet\Models\Machine;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

trait BuildsCertificateScenarios
{
    protected Model&Authenticatable&AgencyMember $employee;

    protected function setUpCertificationScenario(): void
    {
        Storage::fake('vgp-reports');
        config(['certification.go_live_date' => CarbonImmutable::today()->toDateString()]);
        $this->seedPermissions();
        $this->employee = $this->employee();
        $this->actingAs($this->employee);
    }

    protected function machineWithReport(): Machine
    {
        $machine = Machine::factory()->vgpValid()->create();
        VgpReport::factory()->for($machine)->create();

        return $machine;
    }

    protected function customerWithEmail(string $email = 'chantier@exemple.fr'): Customer
    {
        return Customer::factory()->create(['email' => $email]);
    }

    protected function reserve(Machine $machine, Customer $customer, int $startInDays = 3, int $durationInDays = 4): Reservation
    {
        $startDate = CarbonImmutable::today()->addDays($startInDays);

        return app(CreateReservation::class)->handle($this->employee, $machine, $customer, $startDate, $startDate->addDays($durationInDays));
    }

    protected function certificateOf(Reservation $reservation): ReservationCertificate
    {
        return ReservationCertificate::query()->whereBelongsTo($reservation)->firstOrFail();
    }
}
