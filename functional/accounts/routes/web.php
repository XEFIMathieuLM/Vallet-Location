<?php

use Functional\Accounts\Access\AccountsPermission;
use Functional\Accounts\Livewire\KeyAccounts;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified', 'can:'.AccountsPermission::ManageKeyAccounts->value])->group(function () {
    Route::livewire('grands-comptes', KeyAccounts::class)->name('accounts.key-accounts');
});
