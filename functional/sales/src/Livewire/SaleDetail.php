<?php

namespace Functional\Sales\Livewire;

use Flux\Flux;
use Functional\Billing\Money\Money;
use Functional\Fleet\Livewire\Concerns\DisplaysRefusals;
use Functional\Inspection\Livewire\Concerns\ActsAsAgencyMember;
use Functional\Sales\Access\SalesPermission;
use Functional\Sales\Actions\UpdateSaleListing;
use Functional\Sales\Livewire\Concerns\EditsSaleListing;
use Functional\Sales\Models\Sale;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Attributes\On;
use Livewire\Component;
use Spatie\Activitylog\Models\Activity;

/**
 * @property-read Collection<int, Activity> $history
 */
class SaleDetail extends Component
{
    use ActsAsAgencyMember, DisplaysRefusals, EditsSaleListing;

    #[Locked]
    public Sale $sale;

    public function mount(Sale $sale): void
    {
        $this->sale = $sale;
        $this->fillListing();
    }

    /**
     * @return Collection<int, Activity>
     */
    #[Computed]
    public function history(): Collection
    {
        return Activity::query()
            ->whereMorphedTo('subject', $this->sale)
            ->with('causer')
            ->latest('id')
            ->get();
    }

    public function updateListing(UpdateSaleListing $updateSaleListing): void
    {
        Gate::authorize(SalesPermission::Manage->value);
        $this->validate($this->listingRules(), attributes: $this->listingAttributes());

        $this->sale = $updateSaleListing->handle($this->agencyMember(), $this->sale, $this->listing(Money::fromInput($this->askingPrice)));

        Flux::modal('edit-listing')->close();
        Flux::toast(text: __('sales::sales.detail.listing_updated'), variant: 'success');
    }

    #[On('echo-private:sales,.sale.changed')]
    #[On('sale-updated')]
    public function refreshSale(): void
    {
        $this->sale->refresh();
        unset($this->history);
    }

    public function render(): View
    {
        $this->sale->loadMissing(['machine.category', 'machine.agency', 'agency', 'buyer', 'lister']);

        return view('sales::livewire.sale-detail')->title(__('sales::sales.detail.title', ['reference' => $this->sale->machine->reference]));
    }

    private function fillListing(): void
    {
        $this->askingPrice = $this->sale->asking_price->format();
        $this->yearOfManufacture = (string) $this->sale->year_of_manufacture;
        $this->operatingHours = (string) $this->sale->operating_hours;
        $this->condition = $this->sale->condition;
        $this->comment = (string) $this->sale->comment;
    }
}
