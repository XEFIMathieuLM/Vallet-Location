<?php

namespace Functional\Portal\Livewire\Customer;

use Carbon\CarbonImmutable;
use Functional\Fleet\Livewire\Concerns\DisplaysRefusals;
use Functional\Fleet\Models\Machine;
use Functional\Portal\Actions\SendReservationRequest;
use Functional\Portal\Livewire\Concerns\ActsAsCustomerAccount;
use Functional\Portal\Pricing\IndicativePriceFormatter;
use Functional\Portal\Queries\PortalMachineSearch;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Locked;
use Livewire\Component;

class SendRequestForm extends Component
{
    use ActsAsCustomerAccount, DisplaysRefusals;

    public const NAME = 'portal.send-request-form';

    #[Locked]
    public int $machineId;

    #[Locked]
    public string $startDate;

    #[Locked]
    public string $endDate;

    public string $comment = '';

    public function send(SendReservationRequest $sendReservationRequest): void
    {
        $this->validate(['comment' => ['nullable', 'string', 'max:'.config()->integer('portal.comment_max_length')]]);

        $sendReservationRequest->handle(
            $this->customerAccount(),
            Machine::query()->findOrFail($this->machineId),
            CarbonImmutable::parse($this->startDate),
            CarbonImmutable::parse($this->endDate),
            $this->comment,
        );

        session()->flash('request-sent', __('portal::search.request_sent'));
        $this->redirectRoute('portal.requests', navigate: true);
    }

    public function render(PortalMachineSearch $portalMachineSearch, IndicativePriceFormatter $priceFormatter): View
    {
        $machine = Machine::query()->with(['category', 'agency'])->findOrFail($this->machineId);

        return view('portal::livewire.customer.send-request-form', [
            'machine' => $machine,
            'dailyPriceCents' => $portalMachineSearch->dailyPriceCentsOf($machine->category),
            'priceFormatter' => $priceFormatter,
        ]);
    }
}
