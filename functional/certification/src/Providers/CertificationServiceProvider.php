<?php

namespace Functional\Certification\Providers;

use Functional\Booking\Enums\ReservationTransition;
use Functional\Booking\Events\ReservationChanged;
use Functional\Booking\Extensions\ReservationDetailSections;
use Functional\Certification\Listeners\OpenCertificateOnReservationChanged;
use Functional\Certification\Livewire\ReservationCertificateSection;
use Illuminate\Support\Facades\Event;
use Livewire\Livewire;
use Xefi\LaravelOSDD\LayerServiceProvider;

class CertificationServiceProvider extends LayerServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../../config/certification.php', 'certification');
        $this->overrideConfigFrom(__DIR__.'/../../config/filesystems.php', 'filesystems');
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../../database/migrations');
        $this->loadTranslationsFrom(__DIR__.'/../../resources/lang', 'certification');
        $this->loadViewsFrom(__DIR__.'/../../resources/views', 'certification');

        Livewire::component(ReservationCertificateSection::NAME, ReservationCertificateSection::class);
        $this->app->make(ReservationDetailSections::class)->register(ReservationCertificateSection::NAME, 30, ReservationTransition::Departure);
        Event::listen(ReservationChanged::class, OpenCertificateOnReservationChanged::class);
    }
}
