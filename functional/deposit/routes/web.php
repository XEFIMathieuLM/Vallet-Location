<?php

use Functional\Deposit\Access\DepositPermission;
use Functional\Deposit\Livewire\PendingDepositsList;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified'])->prefix('cautions')->group(function () {
    Route::livewire('/', PendingDepositsList::class)->middleware('can:'.DepositPermission::ManageDeposits->value)->name('deposit.pending.index');
});
