<?php

namespace Functional\Billing\Console;

use Carbon\CarbonImmutable;
use Functional\Billing\Actions\RecordFinalPeriod;
use Functional\Billing\Calendar\BillingCalendar;
use Functional\Billing\Enums\BillablePeriodKind;
use Functional\Billing\Enums\TransmissionStatus;
use Functional\Billing\Jobs\SendTransmissionJob;
use Functional\Billing\Models\Transmission;
use Functional\Booking\Enums\ReservationStatus;
use Functional\Booking\Models\Reservation;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\Builder as QueryBuilder;

final class ReconcileCommand extends Command
{
    protected $signature = 'billing:reconcile';

    protected $description = 'Catch up the returns without final period and resend the pending transmissions that are due';

    public function handle(BillingCalendar $billingCalendar, RecordFinalPeriod $recordFinalPeriod): int
    {
        Reservation::query()
            ->where('status', ReservationStatus::Closed)
            ->where('returned_at', '>=', $billingCalendar->goLiveDate()->setTimezone(config()->string('app.timezone')))
            ->whereNotExists(fn (QueryBuilder $finalPeriods) => $finalPeriods
                ->selectRaw('1')
                ->from('billable_periods')
                ->whereColumn('billable_periods.reservation_id', 'reservations.id')
                ->where('billable_periods.kind', BillablePeriodKind::Final->value))
            ->chunkById(100, fn ($reservations) => $reservations->each(
                fn (Reservation $reservation) => $recordFinalPeriod->handle($reservation),
            ));

        Transmission::query()
            ->where('status', TransmissionStatus::Pending)
            ->where(fn (Builder $dueTransmissions): Builder => $dueTransmissions
                ->whereNull('next_attempt_at')
                ->orWhere('next_attempt_at', '<=', CarbonImmutable::now()))
            ->where(fn (Builder $unreservedTransmissions): Builder => $unreservedTransmissions
                ->whereNull('reserved_until')
                ->orWhere('reserved_until', '<=', CarbonImmutable::now()))
            ->pluck('id')
            ->each(fn (int $transmissionId) => SendTransmissionJob::dispatch($transmissionId));

        return self::SUCCESS;
    }
}
