<?php

use Functional\Portal\Access\PortalPermission;
use Functional\Portal\Http\Controllers\CustomerCertificateDownloadController;
use Functional\Portal\Http\Controllers\LogoutController;
use Functional\Portal\Http\Controllers\VerifyCustomerEmailController;
use Functional\Portal\Livewire\Auth\ForgotPassword;
use Functional\Portal\Livewire\Auth\Login;
use Functional\Portal\Livewire\Auth\Register;
use Functional\Portal\Livewire\Auth\ResetPassword;
use Functional\Portal\Livewire\Auth\VerifyEmailNotice;
use Functional\Portal\Livewire\Customer\AccountSettings;
use Functional\Portal\Livewire\Customer\MyRequests;
use Functional\Portal\Livewire\Customer\MyReservations;
use Functional\Portal\Livewire\Customer\Search;
use Functional\Portal\Livewire\Staff\OnlineRequests;
use Illuminate\Support\Facades\Route;

Route::prefix('espace-client')->name('portal.')->group(function () {
    Route::middleware('guest:customer')->group(function () {
        Route::livewire('inscription', Register::class)->name('register');
        Route::livewire('connexion', Login::class)->name('login');
        Route::livewire('mot-de-passe-oublie', ForgotPassword::class)->name('password.request');
        Route::livewire('reinitialisation/{token}', ResetPassword::class)->name('password.reset');
    });

    Route::middleware('auth:customer')->group(function () {
        Route::livewire('confirmer-adresse', VerifyEmailNotice::class)->name('verification.notice');
        Route::get('confirmer-adresse/{id}/{hash}', VerifyCustomerEmailController::class)
            ->middleware(['signed', 'throttle:6,1'])
            ->whereNumber('id')
            ->name('verification.verify');
        Route::post('deconnexion', LogoutController::class)->name('logout');
    });

    Route::middleware(['auth:customer', 'verified:portal.verification.notice'])->group(function () {
        Route::livewire('/', Search::class)->name('search');
        Route::livewire('demandes', MyRequests::class)->name('requests');
        Route::livewire('reservations', MyReservations::class)->name('reservations');
        Route::get('reservations/{reservation}/attestation-vgp', CustomerCertificateDownloadController::class)->whereNumber('reservation')->name('reservations.certificate');
        Route::livewire('compte', AccountSettings::class)->name('account');
    });
});

Route::middleware(['auth', 'verified'])->group(function () {
    Route::livewire('demandes-en-ligne', OnlineRequests::class)->middleware('can:'.PortalPermission::HandleRequests->value)->name('portal.staff.requests');
});
