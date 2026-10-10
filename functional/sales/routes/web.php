<?php

use Functional\Sales\Access\SalesPermission;
use Functional\Sales\Livewire\ListMachineForm;
use Functional\Sales\Livewire\MachineSaleHistory;
use Functional\Sales\Livewire\SaleDetail;
use Functional\Sales\Livewire\SaleList;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified', 'can:'.SalesPermission::Manage->value])->prefix('ventes')->group(function () {
    Route::livewire('/', SaleList::class)->name('sales.index');
    Route::livewire('nouvelle', ListMachineForm::class)->name('sales.create');
    Route::livewire('machines/{machine}', MachineSaleHistory::class)->name('sales.machine-history');
    Route::livewire('{sale}', SaleDetail::class)->name('sales.show');
});
