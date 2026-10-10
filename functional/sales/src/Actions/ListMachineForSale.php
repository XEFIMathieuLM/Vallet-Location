<?php

namespace Functional\Sales\Actions;

use Functional\Fleet\Contracts\AgencyMember;
use Functional\Fleet\Models\Machine;
use Functional\Sales\Data\SaleListing;
use Functional\Sales\Enums\SaleHistoryEvent;
use Functional\Sales\Enums\SaleStatus;
use Functional\Sales\Events\SaleChanged;
use Functional\Sales\Exceptions\InvalidSaleListingException;
use Functional\Sales\Exceptions\MachineAlreadyForSaleException;
use Functional\Sales\Exceptions\MachineAlreadySoldException;
use Functional\Sales\History\SaleHistory;
use Functional\Sales\Models\Sale;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Throwable;

final class ListMachineForSale
{
    private const UNIQUE_VIOLATION = '23505';

    public function __construct(private readonly SaleHistory $saleHistory) {}

    public function handle(Model&AgencyMember $author, Machine $machine, SaleListing $listing): Sale
    {
        if (! $listing->askingPrice->isPositive()) {
            throw InvalidSaleListingException::nonPositivePrice();
        }

        $sale = rescue(
            fn (): Sale => DB::transaction(fn (): Sale => $this->open($author, $machine, $listing)),
            fn (Throwable $exception) => throw $this->translateDatabaseRefusal($exception),
            report: false,
        );

        SaleChanged::dispatch($sale);

        return $sale;
    }

    private function open(Model&AgencyMember $author, Machine $machine, SaleListing $listing): Sale
    {
        $lockedMachine = Machine::query()->lockForUpdate()->findOrFail($machine->id);
        $liveSale = Sale::query()->with('agency')->whereBelongsTo($lockedMachine)->where('status', '<>', SaleStatus::Cancelled)->first();

        if ($liveSale?->status === SaleStatus::Sold) {
            throw MachineAlreadySoldException::for($lockedMachine);
        }

        if ($liveSale !== null) {
            throw MachineAlreadyForSaleException::withOpenSale($liveSale);
        }

        $sale = Sale::query()->create([
            ...$listing->description(),
            'machine_id' => $lockedMachine->id,
            'status' => SaleStatus::Listed,
            'asking_price' => $listing->askingPrice,
            'agency_id' => $author->agencyId(),
            'listed_by' => $author->getKey(),
        ]);
        $this->saleHistory->record($sale, $author, SaleHistoryEvent::Listed, ['price' => $listing->askingPrice->format()]);

        return $sale;
    }

    private function translateDatabaseRefusal(Throwable $exception): Throwable
    {
        if ($exception instanceof QueryException && $exception->getCode() === self::UNIQUE_VIOLATION) {
            return MachineAlreadyForSaleException::concurrent();
        }

        return $exception;
    }
}
