<?php

use Functional\Inspection\Http\Middleware\PhoneLinkHeaders;
use Functional\Inspection\Livewire\CategoryViews;
use Functional\Inspection\Livewire\CategoryViewsIndex;
use Functional\Inspection\Livewire\PhoneCapture;
use Illuminate\Support\Facades\Route;

Route::livewire('photos/{token}', PhoneCapture::class)
    ->middleware(['throttle:60,1', PhoneLinkHeaders::class])
    ->name('inspection.phone');

Route::middleware(['auth', 'verified', 'can:inspection_views.manage'])->group(function () {
    Route::livewire('vues-photos', CategoryViewsIndex::class)->name('inspection.category-views.index');
    Route::livewire('vues-photos/{category}', CategoryViews::class)->name('inspection.category-views.edit');
});
