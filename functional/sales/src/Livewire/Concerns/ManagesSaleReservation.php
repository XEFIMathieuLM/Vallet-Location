<?php

namespace Functional\Sales\Livewire\Concerns;

use Carbon\CarbonImmutable;
use Flux\Flux;
use Functional\Sales\Access\SalesPermission;
use Functional\Sales\Actions\ChangePlannedHandoverDate;
use Functional\Sales\Actions\HandOverSale;
use Functional\Sales\Actions\ReleaseSaleReservation;
use Illuminate\Support\Facades\Gate;

trait ManagesSaleReservation
{
    public string $newPlannedHandoverDate = '';

    public string $releaseReason = '';

    public function changePlannedHandoverDate(ChangePlannedHandoverDate $changePlannedHandoverDate): void
    {
        Gate::authorize(SalesPermission::Manage->value);
        $this->validate(['newPlannedHandoverDate' => ['required', 'date_format:Y-m-d']], attributes: ['newPlannedHandoverDate' => __('sales::sales.offers.planned_handover_date')]);

        $this->sale = $changePlannedHandoverDate->handle($this->agencyMember(), $this->sale, CarbonImmutable::parse($this->newPlannedHandoverDate));

        $this->closeModalWithToast('change-handover-date', 'sales::sales.detail.handover_date_changed');
    }

    public function releaseReservation(ReleaseSaleReservation $releaseSaleReservation): void
    {
        Gate::authorize(SalesPermission::Manage->value);
        $this->validate(['releaseReason' => ['required', 'string', 'max:255']], attributes: ['releaseReason' => __('sales::sales.detail.reason')]);

        $this->sale = $releaseSaleReservation->handle($this->agencyMember(), $this->sale, $this->releaseReason);

        $this->reset('releaseReason');
        $this->closeModalWithToast('release-reservation', 'sales::sales.detail.reservation_released');
    }

    public function handOver(HandOverSale $handOverSale): void
    {
        Gate::authorize(SalesPermission::Manage->value);

        $this->sale = $handOverSale->handle($this->agencyMember(), $this->sale);

        $this->closeModalWithToast('hand-over', 'sales::sales.detail.handed_over');
    }

    private function closeModalWithToast(string $modalName, string $messageKey): void
    {
        Flux::modal($modalName)->close();
        unset($this->history);
        Flux::toast(text: __($messageKey), variant: 'success');
    }
}
