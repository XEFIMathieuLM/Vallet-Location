<?php

namespace Functional\Booking\Access\Controls;

use Functional\Booking\Access\BookingPermission;
use Functional\Booking\Models\Reservation;
use Functional\Fleet\Access\Perimeters\GlobalPerimeter;
use Illuminate\Contracts\Auth\Access\Authorizable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Lomkit\Access\Controls\Control;

class ReservationControl extends Control
{
    /**
     * @var class-string<Model>
     */
    protected string $model = Reservation::class;

    protected function perimeters(): array
    {
        return [
            GlobalPerimeter::new()
                ->allowed(fn (Model $user, string $method): bool => $user instanceof Authorizable && $user->can(BookingPermission::ManageReservations->value))
                ->should(fn (Model $user, Model $reservation): bool => true)
                ->query(fn (Builder $query, Model $user): Builder => $query),
        ];
    }
}
