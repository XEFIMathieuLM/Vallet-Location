<?php

namespace Functional\Inspection\Providers;

use Functional\Booking\Events\ReservationChanged;
use Functional\Booking\Extensions\ReservationDetailSections;
use Functional\Booking\Extensions\ReservationTransitionGuards;
use Functional\Inspection\Access\Controls\CategoryViewControl;
use Functional\Inspection\Access\Controls\DamageControl;
use Functional\Inspection\Guards\PhotosCompleteGuard;
use Functional\Inspection\Listeners\RevokePhotoSessionsOnReservationChanged;
use Functional\Inspection\Livewire\PhotosPanel;
use Functional\Inspection\Support\DamageActions;
use Illuminate\Support\Facades\Event;
use Livewire\Livewire;
use Lomkit\Access\Access;
use Xefi\LaravelOSDD\LayerServiceProvider;

class InspectionServiceProvider extends LayerServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../../config/inspection.php', 'inspection');
        $this->app->singleton(DamageActions::class);
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../../database/migrations');
        $this->loadTranslationsFrom(__DIR__.'/../../resources/lang', 'inspection');
        $this->loadViewsFrom(__DIR__.'/../../resources/views', 'inspection');

        (new Access)->addControls([new CategoryViewControl, new DamageControl]);
        Livewire::component('inspection.photos-panel', PhotosPanel::class);
        $this->app->make(ReservationDetailSections::class)->register('inspection.photos-panel', 10);
        $this->app->make(ReservationTransitionGuards::class)->register(PhotosCompleteGuard::class);
        Event::listen(ReservationChanged::class, RevokePhotoSessionsOnReservationChanged::class);

        $this->withRouting(
            web: __DIR__.'/../../routes/web.php',
            channels: __DIR__.'/../../routes/channels.php',
        );
    }
}
