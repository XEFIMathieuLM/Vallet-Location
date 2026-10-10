<?php

namespace Functional\Accounts\Livewire;

use Flux\Flux;
use Functional\Accounts\Access\AccountsPermission;
use Functional\Accounts\Actions\SetPurchaseOrder;
use Functional\Accounts\Enums\PurchaseOrderSectionStatus;
use Functional\Accounts\Livewire\Concerns\ActsAsAuthor;
use Functional\Accounts\Models\ReservationPurchaseOrder;
use Functional\Accounts\Support\KeyAccounts;
use Functional\Accounts\Support\PurchaseOrderSectionState;
use Functional\Booking\Enums\ReservationTransition;
use Functional\Booking\Models\Reservation;
use Functional\Fleet\Livewire\Concerns\DisplaysRefusals;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Locked;
use Livewire\Component;

class PurchaseOrderSection extends Component
{
    use ActsAsAuthor, DisplaysRefusals;

    public const SECTION = 'accounts.purchase-order-section';

    public const READINESS_EVENT = 'reservation-transition-readiness';

    #[Locked]
    public Reservation $reservation;

    public string $number = '';

    public function mount(Reservation $reservation): void
    {
        $this->reservation = $reservation;
        $this->number = $this->purchaseOrder()->number ?? '';
        $this->announceReadiness();
    }

    public function save(SetPurchaseOrder $setPurchaseOrder): void
    {
        Gate::authorize(AccountsPermission::ManagePurchaseOrders->value);

        $purchaseOrder = $setPurchaseOrder->handle($this->author(), $this->reservation, $this->number);
        $this->number = $purchaseOrder->number;
        Flux::toast(text: __('accounts::purchase_orders.section.saved', ['number' => $purchaseOrder->number]), variant: 'success');
        $this->announceReadiness();
    }

    public function render(): View
    {
        $this->reservation->refresh();
        $purchaseOrder = $this->purchaseOrder();

        return view('accounts::livewire.purchase-order-section', [
            'status' => $this->status($purchaseOrder),
            'purchaseOrder' => $purchaseOrder,
            'isKeyAccount' => app(KeyAccounts::class)->isKeyAccount($this->reservation->customer_id),
        ]);
    }

    private function purchaseOrder(): ?ReservationPurchaseOrder
    {
        return ReservationPurchaseOrder::query()->with(['author', 'agency'])->where('reservation_id', $this->reservation->id)->first();
    }

    private function status(?ReservationPurchaseOrder $purchaseOrder): PurchaseOrderSectionStatus
    {
        return PurchaseOrderSectionState::resolve(
            $this->reservation->customer->type,
            app(KeyAccounts::class)->isKeyAccount($this->reservation->customer_id),
            $purchaseOrder !== null,
            $this->reservation->status,
        );
    }

    private function announceReadiness(): void
    {
        $this->dispatch(
            self::READINESS_EVENT,
            step: ReservationTransition::Departure->value,
            section: self::SECTION,
            is_ready: $this->status($this->purchaseOrder())->isReadyForDeparture(),
        );
    }
}
