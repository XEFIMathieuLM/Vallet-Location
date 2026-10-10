<?php

use Functional\Accounts\Access\AccountsPermission;
use Functional\Accounts\Livewire\KeyAccounts;
use Functional\Accounts\Livewire\MissingPurchaseOrders;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified', 'can:'.AccountsPermission::ManageKeyAccounts->value])->group(function () {
    Route::livewire('grands-comptes', KeyAccounts::class)->name('accounts.key-accounts');
});

Route::middleware(['auth', 'verified', 'can:'.AccountsPermission::ManagePurchaseOrders->value])->group(function () {
    Route::livewire('bons-de-commande', MissingPurchaseOrders::class)->name('accounts.missing-purchase-orders');
});
