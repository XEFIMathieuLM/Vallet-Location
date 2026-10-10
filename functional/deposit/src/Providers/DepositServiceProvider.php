<?php

namespace Functional\Deposit\Providers;

use Functional\Deposit\Access\Controls\DepositControl;
use Functional\Deposit\Access\Controls\DepositRateControl;
use Functional\Booking\Enums\ReservationTransition;
use Functional\Booking\Extensions\ReservationDetailSections;
use Functional\Booking\Extensions\ReservationTransitionGuards;
use Functional\Deposit\Guards\DepositCollectedGuard;
use Functional\Deposit\Livewire\ReservationDepositSection;
use Functional\Deposit\Livewire\Section\CollectDepositForm;
use Functional\Deposit\Livewire\Section\QualifyCustomerForm;
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
        $this->app->make(ReservationDetailSections::class)->register(ReservationDepositSection::NAME, 30, ReservationTransition::Departure);
        $this->app->make(ReservationTransitionGuards::class)->register(DepositCollectedGuard::class);

        $this->withRouting(
            web: __DIR__.'/../../routes/web.php',
            commands: __DIR__.'/../../routes/console.php',
        );
    }
}
