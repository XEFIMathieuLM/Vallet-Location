<?php

namespace Functional\Accounts\Providers;

use Functional\Accounts\Access\Controls\KeyAccountControl;
use Functional\Accounts\Access\Controls\PurchaseOrderControl;
use Functional\Accounts\Badges\KeyAccountBadgeProvider;
use Functional\Accounts\Guards\KeyAccountTypeGuard;
use Functional\Accounts\Guards\PurchaseOrderDepartureGuard;
use Functional\Accounts\Livewire\PurchaseOrderSection;
use Functional\Booking\Enums\ReservationTransition;
use Functional\Booking\Extensions\CustomerBadges;
use Functional\Booking\Extensions\CustomerChangeGuards;
use Functional\Booking\Extensions\ReservationDetailSections;
use Functional\Booking\Extensions\ReservationTransitionGuards;
use Livewire\Livewire;
use Lomkit\Access\Access;
use Xefi\LaravelOSDD\LayerServiceProvider;

class AccountsServiceProvider extends LayerServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../../config/accounts.php', 'accounts');
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../../database/migrations');
        $this->loadTranslationsFrom(__DIR__.'/../../resources/lang', 'accounts');
        $this->loadViewsFrom(__DIR__.'/../../resources/views', 'accounts');

        (new Access)->addControls([new KeyAccountControl, new PurchaseOrderControl]);

        $this->app->make(CustomerChangeGuards::class)->register(KeyAccountTypeGuard::class);
        $this->app->make(CustomerBadges::class)->register(KeyAccountBadgeProvider::class);
        $this->app->make(ReservationTransitionGuards::class)->register(PurchaseOrderDepartureGuard::class);
        Livewire::component(PurchaseOrderSection::SECTION, PurchaseOrderSection::class);
        $this->app->make(ReservationDetailSections::class)->register(PurchaseOrderSection::SECTION, 15, ReservationTransition::Departure);

        $this->withRouting(web: __DIR__.'/../../routes/web.php');
    }
}
