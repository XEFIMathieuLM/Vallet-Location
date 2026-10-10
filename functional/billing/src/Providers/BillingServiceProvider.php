<?php

namespace Functional\Billing\Providers;

use Functional\Billing\Access\Controls\BillingExportControl;
use Functional\Billing\Access\Controls\DamageSettlementControl;
use Functional\Billing\Access\Controls\TransmissionControl;
use Functional\Billing\Console\CloseMonthsCommand;
use Functional\Billing\Console\FakeGatewayCommand;
use Functional\Billing\Console\ReconcileCommand;
use Functional\Billing\Contracts\BillingGateway;
use Functional\Billing\Exceptions\UnknownBillingGatewayException;
use Functional\Billing\Gateways\FakeBillingGateway;
use Functional\Billing\Listeners\RecordFinalPeriodOnReservationClosed;
use Functional\Billing\Livewire\BillingAlert;
use Functional\Billing\Livewire\DamageBillingActions;
use Functional\Billing\Livewire\ReservationBillingSection;
use Functional\Booking\Events\ReservationChanged;
use Functional\Booking\Extensions\ReservationDetailSections;
use Functional\Inspection\Support\DamageActions;
use Illuminate\Support\Facades\Event;
use Livewire\Livewire;
use Lomkit\Access\Access;
use Xefi\LaravelOSDD\LayerServiceProvider;

class BillingServiceProvider extends LayerServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../../config/billing.php', 'billing');
        $this->app->singleton(FakeBillingGateway::class);
        $this->app->bind(BillingGateway::class, function (): BillingGateway {
            $gatewayName = config()->string('billing.gateway');
            $gatewayClass = config()->array('billing.gateways')[$gatewayName] ?? throw UnknownBillingGatewayException::named($gatewayName);

            return $this->app->make($gatewayClass);
        });
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../../database/migrations');
        $this->loadTranslationsFrom(__DIR__.'/../../resources/lang', 'billing');
        $this->loadViewsFrom(__DIR__.'/../../resources/views', 'billing');

        (new Access)->addControls([new TransmissionControl, new DamageSettlementControl, new BillingExportControl]);

        Livewire::component('billing.reservation-section', ReservationBillingSection::class);
        Livewire::component('billing.alert', BillingAlert::class);
        Livewire::component('billing.damage-actions', DamageBillingActions::class);
        $this->app->make(DamageActions::class)->register('billing.damage-actions', 10);
        $this->app->make(ReservationDetailSections::class)->register('billing.reservation-section', 20);
        Event::listen(ReservationChanged::class, RecordFinalPeriodOnReservationClosed::class);

        if ($this->app->runningInConsole()) {
            $this->commands([CloseMonthsCommand::class, FakeGatewayCommand::class, ReconcileCommand::class]);
        }

        $this->withRouting(
            web: __DIR__.'/../../routes/web.php',
            commands: __DIR__.'/../../routes/console.php',
        );
    }
}
