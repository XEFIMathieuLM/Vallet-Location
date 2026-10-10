<?php

namespace Functional\Inspection\Providers;

use Functional\Booking\Enums\ReservationTransition;
use Functional\Booking\Events\ReservationChanged;
use Functional\Booking\Extensions\ReservationDetailSections;
use Functional\Booking\Extensions\ReservationTransitionGuards;
use Functional\Inspection\Access\Controls\CategoryViewControl;
use Functional\Inspection\Access\Controls\DamageControl;
use Functional\Inspection\Extensions\DamageActions;
use Functional\Inspection\Guards\PhotosCompleteGuard;
use Functional\Inspection\Listeners\RevokePhotoSessionsOnReservationChanged;
use Functional\Inspection\Livewire\PhotosPanel;
use Functional\Inspection\Models\Photo;
use Functional\Inspection\Models\PhotoSession;
use Illuminate\Support\Facades\Event;
use Livewire\Livewire;
use Lomkit\Access\Access;
use Xefi\LaravelOSDD\LayerServiceProvider;

class InspectionServiceProvider extends LayerServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../../config/inspection.php', 'inspection');
        $this->overrideConfigFrom(__DIR__.'/../../config/filesystems.php', 'filesystems');

        $maxPhotoKilobytes = config()->integer('inspection.max_photo_kilobytes');
        config([
            'livewire.temporary_file_upload.rules' => ['required', 'file', "max:{$maxPhotoKilobytes}"],
            'media-library.max_file_size' => $maxPhotoKilobytes * 1024,
        ]);
        $this->app->singleton(DamageActions::class);
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../../database/migrations');
        $this->loadTranslationsFrom(__DIR__.'/../../resources/lang', 'inspection');
        $this->loadViewsFrom(__DIR__.'/../../resources/views', 'inspection');

        (new Access)->addControls([new CategoryViewControl, new DamageControl]);
        Livewire::component(PhotosPanel::SECTION, PhotosPanel::class);
        $this->app->make(ReservationDetailSections::class)->register(PhotosPanel::SECTION, 10, ReservationTransition::Departure, ReservationTransition::Return);
        $this->app->make(ReservationTransitionGuards::class)->register(PhotosCompleteGuard::class);
        Event::listen(ReservationChanged::class, RevokePhotoSessionsOnReservationChanged::class);
        config()->push('prunable.models', Photo::class);
        config()->push('prunable.models', PhotoSession::class);

        $this->withRouting(
            web: __DIR__.'/../../routes/web.php',
            channels: __DIR__.'/../../routes/channels.php',
        );
    }
}
