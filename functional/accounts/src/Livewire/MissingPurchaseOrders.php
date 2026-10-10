<?php

namespace Functional\Accounts\Livewire;

use Carbon\CarbonImmutable;
use Flux\Flux;
use Functional\Accounts\Access\AccountsPermission;
use Functional\Accounts\Actions\SetPurchaseOrder;
use Functional\Accounts\Livewire\Concerns\ActsAsAuthor;
use Functional\Accounts\Queries\MissingPurchaseOrders as MissingPurchaseOrdersQuery;
use Functional\Booking\Models\Reservation;
use Functional\Fleet\Livewire\Concerns\DisplaysRefusals;
use Functional\Fleet\Models\Agency;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Gate;
use Livewire\Component;

class MissingPurchaseOrders extends Component
{
    use ActsAsAuthor, DisplaysRefusals;

    public ?int $agencyId = null;

    /**
     * @var array<int|string, string>
     */
    public array $numbers = [];

    public function save(int $reservationId, SetPurchaseOrder $setPurchaseOrder): void
    {
        Gate::authorize(AccountsPermission::ManagePurchaseOrders->value);

        $purchaseOrder = $setPurchaseOrder->handle($this->author(), Reservation::query()->findOrFail($reservationId), $this->numbers[$reservationId] ?? '');
        unset($this->numbers[$reservationId]);
        Flux::toast(text: __('accounts::purchase_orders.section.saved', ['number' => $purchaseOrder->number]), variant: 'success');
    }

    public function render(MissingPurchaseOrdersQuery $missingPurchaseOrders): View
    {
        $reservations = $missingPurchaseOrders->query($this->agencyId)->get();
        $highlightLimit = CarbonImmutable::today()->addDays(config()->integer('accounts.highlight_days_before_departure'));

        return view('accounts::livewire.missing-purchase-orders', [
            'reservations' => $reservations,
            'highlightedIds' => $reservations->filter(fn (Reservation $reservation): bool => $reservation->start_date->lte($highlightLimit))->modelKeys(),
            'agencies' => Agency::query()->orderBy('name')->get(),
        ])->title(__('accounts::purchase_orders.missing.title'));
    }
}
