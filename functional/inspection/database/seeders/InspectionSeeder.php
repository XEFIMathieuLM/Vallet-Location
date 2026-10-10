<?php

namespace Functional\Inspection\Database\Seeders;

use Functional\Booking\Enums\ReservationStatus;
use Functional\Booking\Models\Reservation;
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
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Seeder;

class InspectionSeeder extends Seeder
{
    /**
     * @var Collection<int, Model>
     */
    private Collection $employees;

    public function __construct(private readonly ResolveRequiredViews $resolveRequiredViews) {}

    public function run(): void
    {
        /** @var class-string<Model> $userModel */
        $userModel = config('auth.providers.users.model');
        $this->employees = $userModel::query()->get();

        $this->customizeOneCategory();

        $this->reservationsIn(ReservationStatus::InProgress, 2)->each(
            fn (Reservation $reservation, int $offset) => $offset === 0 ? $this->onRentWithReturnUnderway($reservation) : $this->onRent($reservation),
        );
        $this->reservationsIn(ReservationStatus::Closed, 3)->each(
            fn (Reservation $reservation, int $offset) => $this->returned($reservation, damageCount: $offset < 2 ? 1 : 0, isDamageResolved: $offset === 1),
        );
        $this->reservationsIn(ReservationStatus::Cancelled, 1)->each(fn (Reservation $reservation) => $this->cancelledDuringPhotos($reservation));
    }

    private function customizeOneCategory(): void
    {
        CategoryView::factory()
            ->count(faker()->number(4, 7))
            ->for(MachineCategory::query()->inRandomOrder()->firstOrFail(), 'category')
            ->sequence(fn (Sequence $sequence): array => ['position' => $sequence->index + 1])
            ->create();
    }

    /**
     * @return Collection<int, Reservation>
     */
    private function reservationsIn(ReservationStatus $status, int $count): Collection
    {
        return Reservation::query()->where('status', $status)->orderBy('id')->take($count)->get();
    }

    private function onRentWithReturnUnderway(Reservation $reservation): void
    {
        $views = $this->frozenViews($reservation);

        $this->session($reservation, InspectionStep::Departure)->expired()->createOne();
        $this->session($reservation, InspectionStep::Departure)->revoked(RevocationReason::Replaced)->createOne();
        $this->photograph($views, InspectionStep::Departure, $this->session($reservation, InspectionStep::Departure)->revoked(RevocationReason::StepValidated)->createOne());

        $returnSession = $this->session($reservation, InspectionStep::Return)->createOne();
        $this->photograph($views->take(faker()->number(1, $views->count() - 1)), InspectionStep::Return, $returnSession);
    }

    private function onRent(Reservation $reservation): void
    {
        $views = $this->frozenViews($reservation);

        $this->photograph($views, InspectionStep::Departure, $this->session($reservation, InspectionStep::Departure)->revoked(RevocationReason::StepValidated)->createOne());
    }

    private function returned(Reservation $reservation, int $damageCount, bool $isDamageResolved): void
    {
        $views = $this->frozenViews($reservation);

        foreach (InspectionStep::cases() as $step) {
            $this->photograph($views, $step, $this->session($reservation, $step)->revoked(RevocationReason::StepValidated)->createOne());
        }

        $damage = Damage::factory()
            ->count($damageCount)
            ->for($reservation)
            ->for($views->random(), 'view')
            ->for($this->employees->random(), 'reporter');

        ($isDamageResolved ? $damage->resolvedBy($this->employees->random()) : $damage)->create();
    }

    private function cancelledDuringPhotos(Reservation $reservation): void
    {
        $views = $this->frozenViews($reservation);

        $session = $this->session($reservation, InspectionStep::Departure)->revoked(RevocationReason::ReservationCancelled)->createOne();
        $this->photograph($views->take(1), InspectionStep::Departure, $session);
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
