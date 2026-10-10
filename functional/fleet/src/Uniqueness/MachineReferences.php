<?php

namespace Functional\Fleet\Uniqueness;

use Closure;
use Functional\Fleet\Exceptions\DuplicateMachineReferenceException;
use Functional\Fleet\Models\Machine;
use Illuminate\Database\QueryException;
use Illuminate\Support\Str;
use Throwable;

final class MachineReferences
{
    private const UNIQUE_VIOLATION = '23505';

    public static function normalize(string $reference): string
    {
        return Str::upper(trim($reference));
    }

    public function isTaken(string $reference, ?int $exceptMachineId = null): bool
    {
        return Machine::query()
            ->whereRaw('upper(btrim(reference)) = ?', [self::normalize($reference)])
            ->when($exceptMachineId !== null, fn ($query) => $query->whereKeyNot($exceptMachineId))
            ->exists();
    }

    /**
     * @template TResult
     *
     * @param  Closure(): TResult  $write
     * @return TResult
     */
    public function writeUnique(string $reference, ?int $exceptMachineId, Closure $write): mixed
    {
        if ($this->isTaken($reference, $exceptMachineId)) {
            throw DuplicateMachineReferenceException::for(self::normalize($reference));
        }

        return rescue(
            $write,
            fn (Throwable $exception) => throw $this->translateUniqueViolation($exception, $reference),
            report: false,
        );
    }

    private function translateUniqueViolation(Throwable $exception, string $reference): Throwable
    {
        if ($exception instanceof QueryException && $exception->getCode() === self::UNIQUE_VIOLATION) {
            return DuplicateMachineReferenceException::for(self::normalize($reference));
        }

        return $exception;
    }
}
