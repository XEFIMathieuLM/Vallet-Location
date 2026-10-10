<?php

namespace Functional\Deposit\Providers;

use Functional\Booking\Enums\ReservationTransition;
use Functional\Booking\Events\ReservationChanged;
use Functional\Booking\Extensions\ReservationDetailSections;
use Functional\Booking\Extensions\ReservationTransitionGuards;
use Functional\Deposit\Access\Controls\DepositControl;
use Functional\Deposit\Access\Controls\DepositRateControl;
use Functional\Deposit\Console\ReconcileDeposits;
use Functional\Deposit\Guards\DepositCollectedGuard;
use Functional\Deposit\Listeners\SyncDepositOnDamageChanged;
use Functional\Deposit\Listeners\SyncDepositOnReservationChanged;
use Functional\Deposit\Livewire\ReservationDepositSection;
use Functional\Deposit\Livewire\Section\CloseDepositActions;
use Functional\Deposit\Livewire\Section\CollectDepositForm;
use Functional\Deposit\Livewire\Section\CorrectPaymentForm;
use Functional\Deposit\Livewire\Section\QualifyCustomerForm;
use Functional\Inspection\Events\DamageChanged;
use Illuminate\Support\Facades\Event;
use Livewire\Livewire;
use Lomkit\Access\Access;
use Xefi\LaravelOSDD\LayerServiceProvider;

class DepositServiceProvider extends LayerServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../../config/deposit.php', 'deposit');
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../../database/migrations');
        $this->loadTranslationsFrom(__DIR__.'/../../resources/lang', 'deposit');
        $this->loadViewsFrom(__DIR__.'/../../resources/views', 'deposit');

        (new Access)->addControls([new DepositControl, new DepositRateControl]);

        Livewire::component(ReservationDepositSection::NAME, ReservationDepositSection::class);
        Livewire::component(CollectDepositForm::NAME, CollectDepositForm::class);
        Livewire::component(QualifyCustomerForm::NAME, QualifyCustomerForm::class);
        Livewire::component(CorrectPaymentForm::NAME, CorrectPaymentForm::class);
        Livewire::component(CloseDepositActions::NAME, CloseDepositActions::class);
        $this->app->make(ReservationDetailSections::class)->register(ReservationDepositSection::NAME, 30, ReservationTransition::Departure);
        $this->app->make(ReservationTransitionGuards::class)->register(DepositCollectedGuard::class);
        Event::listen(ReservationChanged::class, SyncDepositOnReservationChanged::class);
        Event::listen(DamageChanged::class, SyncDepositOnDamageChanged::class);

        if ($this->app->runningInConsole()) {
            $this->commands([ReconcileDeposits::class]);
        }

        $this->withRouting(
            web: __DIR__.'/../../routes/web.php',
            commands: __DIR__.'/../../routes/console.php',
        );
    }
}
