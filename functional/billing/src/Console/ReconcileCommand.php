<?php

namespace Functional\Billing\Console;

use Carbon\CarbonImmutable;
use Functional\Billing\Actions\RecordFinalPeriod;
use Functional\Billing\Calendar\BillingCalendar;
use Functional\Billing\Enums\BillablePeriodKind;
use Functional\Billing\Enums\TransmissionStatus;
use Functional\Billing\Jobs\SendTransmissionJob;
use Functional\Billing\Models\BillablePeriod;
use Functional\Billing\Models\Transmission;
use Functional\Booking\Enums\ReservationStatus;
use Functional\Booking\Models\Reservation;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

final class ReconcileCommand extends Command
{
    protected $signature = 'billing:reconcile';

    protected $description = 'Catch up the returns without final period and resend the pending transmissions that are due';

    public function handle(BillingCalendar $billingCalendar, RecordFinalPeriod $recordFinalPeriod): int
    {
        $caughtUpReservationsCount = 0;

        Reservation::query()
            ->where('status', ReservationStatus::Closed)
            ->where('returned_at', '>=', $billingCalendar->goLiveDate()->setTimezone(config()->string('app.timezone')))
            ->whereNotIn('id', BillablePeriod::query()->select('reservation_id')->where('kind', BillablePeriodKind::Final))
            ->chunkById(100, function (Collection $reservations) use ($recordFinalPeriod, &$caughtUpReservationsCount): void {
                $reservations->each(function (Reservation $reservation) use ($recordFinalPeriod): void {
                    $this->line("Recording the final period of reservation #{$reservation->id}.");
                    $recordFinalPeriod->handle($reservation);
                });
                $caughtUpReservationsCount += $reservations->count();
            });

        $dueTransmissionIds = Transmission::query()
            ->where('status', TransmissionStatus::Pending)
            ->where(fn (Builder $dueTransmissions): Builder => $dueTransmissions
                ->whereNull('next_attempt_at')
                ->orWhere('next_attempt_at', '<=', CarbonImmutable::now()))
            ->where(fn (Builder $unreservedTransmissions): Builder => $unreservedTransmissions
                ->whereNull('reserved_until')
                ->orWhere('reserved_until', '<=', CarbonImmutable::now()))
            ->pluck('id');

        $dueTransmissionIds->each(function (int $transmissionId): void {
            $this->line("Sending transmission #{$transmissionId}.");
            SendTransmissionJob::dispatch($transmissionId);
        });

        $this->info("Reconciled {$caughtUpReservationsCount} reservation(s) and queued {$dueTransmissionIds->count()} transmission(s).");

        return self::SUCCESS;
    }
}
