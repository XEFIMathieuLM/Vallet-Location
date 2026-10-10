<?php

namespace Functional\Sales\Livewire;

use Carbon\CarbonImmutable;
use Flux\Flux;
use Functional\Billing\Money\Money;
use Functional\Fleet\Livewire\Concerns\DisplaysRefusals;
use Functional\Inspection\Livewire\Concerns\ActsAsAgencyMember;
use Functional\Sales\Access\SalesPermission;
use Functional\Sales\Actions\AcceptOffer;
use Functional\Sales\Actions\RecordOffer;
use Functional\Sales\Actions\RejectOffer;
use Functional\Sales\Actions\WithdrawOffer;
use Functional\Sales\Livewire\Concerns\ChoosesBuyer;
use Functional\Sales\Models\Sale;
use Functional\Sales\Models\SaleOffer;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Component;

/**
 * @property-read Collection<int, SaleOffer> $offers
 */
class SaleOffers extends Component
{
    use ActsAsAgencyMember, ChoosesBuyer, DisplaysRefusals;

    #[Locked]
    public int $saleId;

    public string $amount = '';

    public string $offeredOn = '';

    #[Locked]
    public ?int $offerToAcceptId = null;

    public string $plannedHandoverDate = '';

    public function mount(Sale $sale): void
    {
        $this->saleId = $sale->id;
        $this->offeredOn = CarbonImmutable::today()->toDateString();
    }

    /**
     * @return Collection<int, SaleOffer>
     */
    #[Computed]
    public function offers(): Collection
    {
        return SaleOffer::query()->with('customer')->where('sale_id', $this->saleId)->latest('id')->get();
    }

    public function record(RecordOffer $recordOffer): void
    {
        Gate::authorize(SalesPermission::Manage->value);
        $this->validate([...$this->buyerRules(), 'amount' => ['required', 'regex:'.Money::INPUT_PATTERN], 'offeredOn' => ['required', 'date_format:Y-m-d']], attributes: [
            ...$this->buyerAttributes(),
            'amount' => __('sales::sales.offers.amount'),
            'offeredOn' => __('sales::sales.offers.offered_on'),
        ]);

        $recordOffer->handle($this->agencyMember(), $this->sale(), $this->selectedBuyer(), Money::fromInput($this->amount), CarbonImmutable::parse($this->offeredOn));

        $this->reset('amount', 'customerId', 'customerSearch', 'isNewCustomer', 'newCustomerName', 'newCustomerPhone', 'newCustomerEmail', 'newCustomerType');
        $this->done('sales::sales.offers.recorded');
    }

    public function confirmAcceptance(int $offerId): void
    {
        $this->offerToAcceptId = $offerId;
        Flux::modal('accept-offer')->show();
    }

    public function accept(AcceptOffer $acceptOffer): void
    {
        Gate::authorize(SalesPermission::Manage->value);
        $this->validate(['plannedHandoverDate' => ['required', 'date_format:Y-m-d']], attributes: ['plannedHandoverDate' => __('sales::sales.offers.planned_handover_date')]);

        $acceptOffer->handle($this->agencyMember(), $this->offer((int) $this->offerToAcceptId), CarbonImmutable::parse($this->plannedHandoverDate));

        Flux::modal('accept-offer')->close();
        $this->reset('offerToAcceptId', 'plannedHandoverDate');
        $this->done('sales::sales.offers.accepted');
    }

    public function reject(int $offerId, RejectOffer $rejectOffer): void
    {
        Gate::authorize(SalesPermission::Manage->value);
        $rejectOffer->handle($this->agencyMember(), $this->offer($offerId));
        $this->done('sales::sales.offers.rejected');
    }

    public function withdraw(int $offerId, WithdrawOffer $withdrawOffer): void
    {
        Gate::authorize(SalesPermission::Manage->value);
        $withdrawOffer->handle($this->agencyMember(), $this->offer($offerId));
        $this->done('sales::sales.offers.withdrawn');
    }

    public function render(): View
    {
        return view('sales::livewire.sale-offers', ['sale' => $this->sale()]);
    }

    private function sale(): Sale
    {
        return Sale::query()->findOrFail($this->saleId);
    }

    private function offer(int $offerId): SaleOffer
    {
        return SaleOffer::query()->where('sale_id', $this->saleId)->findOrFail($offerId);
    }

    private function done(string $messageKey): void
    {
        unset($this->offers);
        $this->dispatch('sale-updated');
        Flux::toast(text: __($messageKey), variant: 'success');
    }
}
