<?php

namespace Functional\Sales\Providers;

use Functional\Billing\Extensions\BillableSources;
use Functional\Booking\Extensions\ReservationRequestGuards;
use Functional\Fleet\Extensions\MachineBadges;
use Functional\Fleet\Extensions\MachineRetirementGuards;
use Functional\Sales\Access\Controls\SaleControl;
use Functional\Sales\Badges\SaleMachineBadges;
use Functional\Sales\Billing\SaleBillableSource;
use Functional\Sales\Guards\OpenSaleRetirementGuard;
use Functional\Sales\Guards\ReservedSaleReservationGuard;
use Functional\Sales\Livewire\SaleOffers;
use Livewire\Livewire;
use Lomkit\Access\Access;
use Xefi\LaravelOSDD\LayerServiceProvider;

class SalesServiceProvider extends LayerServiceProvider
{
    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../../database/migrations');
        $this->loadTranslationsFrom(__DIR__.'/../../resources/lang', 'sales');
        $this->loadViewsFrom(__DIR__.'/../../resources/views', 'sales');

        (new Access)->addControls([new SaleControl]);
        Livewire::component('sales.sale-offers', SaleOffers::class);
        $this->app->make(MachineBadges::class)->register(SaleMachineBadges::class);
        $this->app->make(ReservationRequestGuards::class)->register(ReservedSaleReservationGuard::class);
        $this->app->make(MachineRetirementGuards::class)->register(OpenSaleRetirementGuard::class);
        $this->app->make(BillableSources::class)->register(SaleBillableSource::class);

        $this->withRouting(
            web: __DIR__.'/../../routes/web.php',
            channels: __DIR__.'/../../routes/channels.php',
        );
    }
}
