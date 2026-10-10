<?php

use Functional\Certification\Enums\CertificationPermission;
use Functional\Certification\Http\Controllers\VgpReportFileController;
use Functional\Certification\Livewire\CertificatesToHandle;
use Functional\Certification\Livewire\MachineVgpReports;
use Functional\Certification\Livewire\VgpMachines;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified', 'can:'.CertificationPermission::Manage->value])->prefix('vgp')->group(function () {
    Route::livewire('machines', VgpMachines::class)->name('certification.machines');
    Route::livewire('machines/{machine}', MachineVgpReports::class)->whereNumber('machine')->name('certification.machines.show');
    Route::livewire('attestations', CertificatesToHandle::class)->name('certification.certificates');
    Route::get('rapports/{report}/fichier', VgpReportFileController::class)->whereNumber('report')->name('certification.reports.file');
});
