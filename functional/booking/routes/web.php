<?php

use Functional\Booking\Livewire\AvailabilitySearch;
use Functional\Booking\Livewire\CreateReservationForm;
use Functional\Booking\Livewire\Planning;
use Functional\Booking\Livewire\ReservationDetail;
use Functional\Booking\Livewire\ReservationList;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified', 'can:reservations.manage'])->group(function () {
    Route::livewire('disponibilites', AvailabilitySearch::class)->name('availability.index');
    Route::livewire('reservations', ReservationList::class)->name('reservations.index');
    Route::livewire('reservations/nouvelle', CreateReservationForm::class)->name('reservations.create');
    Route::livewire('planning', Planning::class)->name('planning.index');
    Route::livewire('reservations/{reservation}', ReservationDetail::class)->whereNumber('reservation')->name('reservations.show');
});
