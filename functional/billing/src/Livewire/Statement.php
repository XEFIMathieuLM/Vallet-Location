<?php

namespace Functional\Billing\Livewire;

use Carbon\CarbonImmutable;
use Functional\Billing\Calendar\BillingCalendar;
use Functional\Billing\Money\EuroAmount;
use Functional\Billing\Queries\BillingStatement;
use Functional\Fleet\Models\Agency;
use Functional\Inspection\Models\Damage;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Url;
use Livewire\Component;

class Statement extends Component
{
    #[Url]
    public ?int $agencyId = null;

    #[Url]
    public string $month = '';

    public function mount(BillingCalendar $billingCalendar): void
    {
        $this->month = $this->month !== '' ? $this->month : $billingCalendar->today()->format('Y-m');
    }

    public function render(BillingStatement $billingStatement, BillingCalendar $billingCalendar, EuroAmount $euroAmount): View
    {
        $firstDay = $this->firstDayOfMonth($billingCalendar);
        $today = $billingCalendar->today();

        return view('billing::livewire.statement', [
            'agencies' => Agency::query()->orderBy('name')->get(),
            'statementFigures' => $billingStatement->for($this->agencyId, $firstDay, $firstDay->endOfMonth()->startOfDay()),
            'euroAmount' => $euroAmount,
            'ageInDays' => fn (Damage $damage): int => (int) $billingCalendar->dateOf($damage->reported_at)->diffInDays($today),
            'overdueDays' => config()->integer('billing.damage_overdue_days'),
        ])->title(__('billing::statement.title'));
    }

    private function firstDayOfMonth(BillingCalendar $billingCalendar): CarbonImmutable
    {
        $isValidMonth = preg_match('/^\d{4}-\d{2}$/', $this->month) === 1 && CarbonImmutable::canBeCreatedFromFormat($this->month, 'Y-m');

        return $isValidMonth
            ? CarbonImmutable::createFromFormat('!Y-m', $this->month, $billingCalendar->today()->timezone) ?: $billingCalendar->today()->startOfMonth()
            : $billingCalendar->today()->startOfMonth();
    }
}
