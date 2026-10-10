<?php

namespace Functional\Certification\Providers;

use Functional\Booking\Enums\ReservationTransition;
use Functional\Booking\Events\CustomerChanged;
use Functional\Booking\Events\ReservationChanged;
use Functional\Booking\Extensions\ReservationDetailSections;
use Functional\Booking\Extensions\ReservationTransitionGuards;
use Functional\Certification\Access\Controls\ReservationCertificateControl;
use Functional\Certification\Access\Controls\VgpReportControl;
use Functional\Certification\Console\ReconcileCommand;
use Functional\Certification\Events\VgpReportDeposited;
use Functional\Certification\Guards\CertificateDeliveredGuard;
use Functional\Certification\Listeners\OpenCertificateOnReservationChanged;
use Functional\Certification\Listeners\ResolveCertificatesOnCustomerChanged;
use Functional\Certification\Listeners\ResolveCertificatesOnReportDeposited;
use Functional\Certification\Livewire\CertificationAlert;
use Functional\Certification\Livewire\CustomerEmailForm;
use Functional\Certification\Livewire\HandDeliveryButton;
use Functional\Certification\Livewire\ReservationCertificateSection;
use Illuminate\Support\Facades\Event;
use Livewire\Livewire;
use Lomkit\Access\Access;
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

        (new Access)->addControls([new VgpReportControl, new ReservationCertificateControl]);

        Livewire::component(ReservationCertificateSection::NAME, ReservationCertificateSection::class);
        Livewire::component(CertificationAlert::NAME, CertificationAlert::class);
        Livewire::component(CustomerEmailForm::NAME, CustomerEmailForm::class);
        Livewire::component(HandDeliveryButton::NAME, HandDeliveryButton::class);
        $this->app->make(ReservationTransitionGuards::class)->register(CertificateDeliveredGuard::class);
        $this->app->make(ReservationDetailSections::class)->register(ReservationCertificateSection::NAME, 30, ReservationTransition::Departure);
        Event::listen(ReservationChanged::class, OpenCertificateOnReservationChanged::class);
        Event::listen(VgpReportDeposited::class, ResolveCertificatesOnReportDeposited::class);
        Event::listen(CustomerChanged::class, ResolveCertificatesOnCustomerChanged::class);

        if ($this->app->runningInConsole()) {
            $this->commands([ReconcileCommand::class]);
        }

        $this->withRouting(
            web: __DIR__.'/../../routes/web.php',
            commands: __DIR__.'/../../routes/console.php',
        );
    }
}
