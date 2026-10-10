<?php

namespace Functional\Inspection\Database\Seeders;

use App\Models\User;
use Carbon\CarbonImmutable;
use Functional\Booking\Enums\ReservationStatus;
use Functional\Booking\Models\Reservation;
use Functional\Fleet\Enums\MachineStatus;
use Functional\Fleet\Models\Agency;
use Functional\Fleet\Models\Machine;
use Functional\Fleet\Models\MachineCategory;
use Functional\Inspection\Actions\ResolveRequiredViews;
use Functional\Inspection\Database\Factories\PhotoSessionFactory;
use Functional\Inspection\Enums\InspectionStep;
use Functional\Inspection\Enums\RevocationReason;
use Functional\Inspection\Models\CategoryView;
use Functional\Inspection\Models\Damage;
use Functional\Inspection\Models\Photo;
use Functional\Inspection\Models\PhotoSession;
use Functional\Inspection\Models\ReservationView;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\Sequence;
use Illuminate\Database\Seeder;

class InspectionSeeder extends Seeder
{
    /**
     * @var Collection<int, User>
     */
    private Collection $employees;

    public function __construct(private readonly ResolveRequiredViews $resolveRequiredViews) {}

    public function run(): void
    {
        $this->employees = User::query()->get();

        $this->customizeOneCategory();
        $this->awaitingDeparture();
        $this->onRent();
        $this->returned(isDamageResolved: false);
        $this->returned(isDamageResolved: true);
        $this->cancelledDuringPhotos();
    }

    private function customizeOneCategory(): void
    {
        $category = MachineCategory::query()->inRandomOrder()->firstOrFail();

        CategoryView::factory()
            ->count(faker()->number(4, 7))
            ->for($category, 'category')
            ->sequence(fn (Sequence $sequence): array => ['position' => $sequence->index + 1])
            ->create();
    }

    private function awaitingDeparture(): void
    {
        $reservation = $this->reservation(ReservationStatus::Confirmed, MachineStatus::Available, CarbonImmutable::today());
        $views = $this->frozenViews($reservation);

        $this->session($reservation, InspectionStep::Departure)->expired()->createOne();
        $this->session($reservation, InspectionStep::Departure)->revoked(RevocationReason::Replaced)->createOne();
        $activeSession = $this->session($reservation, InspectionStep::Departure)->createOne();

        $this->photograph($views->take(faker()->number(1, $views->count() - 1)), InspectionStep::Departure, $activeSession);
    }

    private function onRent(): void
    {
        $reservation = $this->reservation(ReservationStatus::InProgress, MachineStatus::RentedOut, CarbonImmutable::today()->subDays(faker()->number(1, 5)));
        $views = $this->frozenViews($reservation);

        $departureSession = $this->session($reservation, InspectionStep::Departure)->revoked(RevocationReason::StepValidated)->createOne();
        $this->photograph($views, InspectionStep::Departure, $departureSession);

        $returnSession = $this->session($reservation, InspectionStep::Return)->createOne();
        $this->photograph($views->take(faker()->number(1, $views->count() - 1)), InspectionStep::Return, $returnSession);
    }

    private function returned(bool $isDamageResolved): void
    {
        $reservation = $this->reservation(ReservationStatus::Closed, MachineStatus::Available, CarbonImmutable::today()->subDays(faker()->number(10, 60)));
        $views = $this->frozenViews($reservation);

        foreach (InspectionStep::cases() as $step) {
            $this->photograph($views, $step, $this->session($reservation, $step)->revoked(RevocationReason::StepValidated)->createOne());
        }

        $damage = Damage::factory()
            ->for($reservation)
            ->for($views->random(), 'view')
            ->for($this->employees->random(), 'reporter');

        ($isDamageResolved ? $damage->resolved()->for($this->employees->random(), 'resolver') : $damage)->create();
    }

    private function cancelledDuringPhotos(): void
    {
        $reservation = $this->reservation(ReservationStatus::Cancelled, MachineStatus::Available, CarbonImmutable::today()->addDays(faker()->number(1, 10)));
        $views = $this->frozenViews($reservation);

        $session = $this->session($reservation, InspectionStep::Departure)->revoked(RevocationReason::ReservationCancelled)->createOne();
        $this->photograph($views->take(1), InspectionStep::Departure, $session);
    }

    private function reservation(ReservationStatus $status, MachineStatus $machineStatus, CarbonImmutable $startDate): Reservation
    {
        $machine = Machine::factory()
            ->recycle(MachineCategory::query()->get())
            ->recycle(Agency::query()->get())
            ->withStatus($machineStatus)
            ->create();

        return Reservation::factory()
            ->for($machine)
            ->recycle(Agency::query()->get())
            ->for($this->employees->random(), 'author')
            ->between($startDate, $startDate->addDays(faker()->number(2, 6)))
            ->withStatus($status)
            ->create();
    }

    /**
     * @return Collection<int, ReservationView>
     */
    private function frozenViews(Reservation $reservation): Collection
    {
        $labels = $this->resolveRequiredViews->forReservation($reservation);

        return ReservationView::factory()
            ->count(count($labels))
            ->for($reservation)
            ->sequence(fn (Sequence $sequence): array => ['label' => $labels[$sequence->index], 'position' => $sequence->index + 1])
            ->create();
    }

    private function session(Reservation $reservation, InspectionStep $step): PhotoSessionFactory
    {
        return PhotoSession::factory()
            ->for($reservation)
            ->forStep($step)
            ->for($this->employees->random(), 'author');
    }

    /**
     * @param  Collection<int, ReservationView>  $views
     */
    private function photograph(Collection $views, InspectionStep $step, PhotoSession $session): void
    {
        $views->each(fn (ReservationView $view) => Photo::factory()
            ->forView($view, $step)
            ->for($session, 'session')
            ->withFile()
            ->create());
    }
}
