<?php

use Functional\Fleet\Access\FleetPermission;
use Functional\Fleet\Livewire\ImportFleetForm;
use Functional\Fleet\Livewire\MachineForm;
use Functional\Fleet\Livewire\MachineIndex;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified', 'can:'.FleetPermission::ManageMachines->value])->group(function () {
    Route::livewire('machines', MachineIndex::class)->name('machines.index');
    Route::livewire('machines/nouvelle', MachineForm::class)->name('machines.create');
    Route::livewire('machines/import', ImportFleetForm::class)->name('machines.import');
    Route::livewire('machines/{machine}/modifier', MachineForm::class)->whereNumber('machine')->name('machines.edit');
});
