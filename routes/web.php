<?php

use App\Livewire\Users\UserIndex;
use Illuminate\Support\Facades\Route;

Route::view('/', 'welcome')->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::view('dashboard', 'dashboard')->name('dashboard');
    Route::livewire('salaries', UserIndex::class)->middleware('can:users.manage')->name('users.index');
});

require __DIR__.'/settings.php';
