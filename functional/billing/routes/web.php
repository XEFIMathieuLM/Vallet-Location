<?php

use Functional\Billing\Enums\BillingPermission;
use Functional\Billing\Http\Controllers\BillingExportDownloadController;
use Functional\Billing\Livewire\Exports;
use Functional\Billing\Livewire\Statement;
use Functional\Billing\Livewire\Transmissions;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified', 'can:'.BillingPermission::Manage->value])->prefix('facturation')->group(function () {
    Route::livewire('transmissions', Transmissions::class)->name('billing.transmissions');
    Route::livewire('exports', Exports::class)->name('billing.exports');
    Route::livewire('releve', Statement::class)->name('billing.statement');
    Route::get('exports/{billingExport}/telechargement', BillingExportDownloadController::class)->name('billing.exports.download');
});
