<?php

use Functional\Booking\Access\BookingPermission;
use Functional\Inspection\Access\InspectionPermission;
use Functional\Inspection\Http\Controllers\PhotoFileController;
use Functional\Inspection\Http\Middleware\PhoneLinkHeaders;
use Functional\Inspection\Livewire\CategoryViews;
use Functional\Inspection\Livewire\CategoryViewsIndex;
use Functional\Inspection\Livewire\Comparison;
use Functional\Inspection\Livewire\DamagesList;
use Functional\Inspection\Livewire\PhoneCapture;
use Illuminate\Support\Facades\Route;

Route::livewire('photos/{token}', PhoneCapture::class)
    ->middleware(['throttle:60,1', PhoneLinkHeaders::class])
    ->name('inspection.phone');

Route::middleware(['auth', 'verified', 'can:'.BookingPermission::ManageReservations->value])->group(function () {
    Route::get('photo-fichiers/{photo}/{conversion}', PhotoFileController::class)->name('inspection.photo-file');
    Route::livewire('reservations/{reservation}/photos', Comparison::class)->name('inspection.comparison');
});

Route::middleware(['auth', 'verified', 'can:'.InspectionPermission::ManageDamages->value])->group(function () {
    Route::livewire('degats', DamagesList::class)->name('inspection.damages');
});

Route::middleware(['auth', 'verified', 'can:'.InspectionPermission::ManageInspectionViews->value])->group(function () {
    Route::livewire('vues-photos', CategoryViewsIndex::class)->name('inspection.category-views.index');
    Route::livewire('vues-photos/{category}', CategoryViews::class)->name('inspection.category-views.edit');
});
