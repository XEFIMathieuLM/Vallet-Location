<?php

namespace Functional\Booking\Tests\Doubles;

use Functional\Booking\Models\Reservation;
use Livewire\Component;

final class TestReservationSection extends Component
{
    public Reservation $reservation;

    public function render(): string
    {
        return '<div>Section de test</div>';
    }
}
