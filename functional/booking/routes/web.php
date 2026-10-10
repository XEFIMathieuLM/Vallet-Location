<?php

use Functional\Booking\Livewire\AvailabilitySearch;
use Functional\Booking\Livewire\CreateReservationForm;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified', 'can:reservations.manage'])->group(function () {
    Route::livewire('disponibilites', AvailabilitySearch::class)->name('availability.index');
    Route::livewire('reservations/nouvelle', CreateReservationForm::class)->name('reservations.create');
});
