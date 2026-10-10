<?php

namespace Functional\Booking\Providers;

use Functional\Booking\Access\Controls\ReservationControl;
use Lomkit\Access\Access;
use Xefi\LaravelOSDD\LayerServiceProvider;

class BookingServiceProvider extends LayerServiceProvider
{
    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../../database/migrations');
        $this->loadTranslationsFrom(__DIR__.'/../../resources/lang', 'booking');
        $this->loadViewsFrom(__DIR__.'/../../resources/views', 'booking');

        (new Access)->addControls([new ReservationControl]);

        $this->withRouting(
            web: __DIR__.'/../../routes/web.php',
            commands: __DIR__.'/../../routes/console.php',
        );
    }
}
