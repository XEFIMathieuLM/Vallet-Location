<?php

namespace Functional\Booking\Providers;

use Functional\Booking\Access\Controls\ReservationControl;
use Functional\Booking\Console\FlagLateReturns;
use Functional\Booking\Extensions\CustomerBadges;
use Functional\Booking\Extensions\CustomerChangeGuards;
use Functional\Booking\Extensions\ReservationDetailSections;
use Functional\Booking\Extensions\ReservationRequestGuards;
use Functional\Booking\Extensions\ReservationTransitionGuards;
use Functional\Booking\Guards\ActiveReservationsRetirementGuard;
use Functional\Booking\Listeners\RefreshConflictsOnMachineChanged;
use Functional\Fleet\Events\MachineChanged;
use Functional\Fleet\Extensions\MachineRetirementGuards;
use Illuminate\Support\Facades\Event;
use Lomkit\Access\Access;
use Xefi\LaravelOSDD\LayerServiceProvider;

class BookingServiceProvider extends LayerServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(ReservationTransitionGuards::class);
        $this->app->singleton(ReservationDetailSections::class);
        $this->app->singleton(CustomerChangeGuards::class);
        $this->app->singleton(CustomerBadges::class);
        $this->app->singleton(ReservationRequestGuards::class);
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../../database/migrations');
        $this->loadTranslationsFrom(__DIR__.'/../../resources/lang', 'booking');
        $this->loadViewsFrom(__DIR__.'/../../resources/views', 'booking');

        (new Access)->addControls([new ReservationControl]);
        $this->app->make(MachineRetirementGuards::class)->register(ActiveReservationsRetirementGuard::class);

        Event::listen(MachineChanged::class, RefreshConflictsOnMachineChanged::class);

        if ($this->app->runningInConsole()) {
            $this->commands([FlagLateReturns::class]);
        }

        $this->withRouting(
            web: __DIR__.'/../../routes/web.php',
            commands: __DIR__.'/../../routes/console.php',
        );
    }
}
