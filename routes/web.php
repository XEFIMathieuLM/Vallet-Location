<?php

use App\Access\AppPermission;
use App\Livewire\Dashboard\Dashboard;
use App\Livewire\Users\UserIndex;
use Illuminate\Support\Facades\Route;

Route::view('/', 'welcome')->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::livewire('dashboard', Dashboard::class)->name('dashboard');
    Route::livewire('salaries', UserIndex::class)->middleware('can:'.AppPermission::ManageUsers->value)->name('users.index');
});

require __DIR__.'/settings.php';
