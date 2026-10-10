<?php

use Functional\Deposit\Access\DepositPermission;
use Functional\Deposit\Livewire\DepositRateForm;
use Functional\Deposit\Livewire\DepositRatesIndex;
use Functional\Deposit\Livewire\PendingDepositsList;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified'])->prefix('cautions')->group(function () {
    Route::livewire('/', PendingDepositsList::class)->middleware('can:'.DepositPermission::ManageDeposits->value)->name('deposit.pending.index');

    Route::middleware('can:'.DepositPermission::ManageDepositRates->value)->group(function () {
        Route::livewire('montants', DepositRatesIndex::class)->name('deposit.rates.index');
        Route::livewire('montants/{category}', DepositRateForm::class)->name('deposit.rates.edit');
    });
});
