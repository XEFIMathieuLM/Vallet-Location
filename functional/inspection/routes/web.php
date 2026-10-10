<?php

use Functional\Inspection\Http\Middleware\PhoneLinkHeaders;
use Functional\Inspection\Livewire\PhoneCapture;
use Illuminate\Support\Facades\Route;

Route::livewire('photos/{token}', PhoneCapture::class)
    ->middleware(['throttle:60,1', PhoneLinkHeaders::class])
    ->name('inspection.phone');
