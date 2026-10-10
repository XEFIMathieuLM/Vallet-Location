<?php

namespace Functional\Inspection\Actions;

use App\Models\User;
use Carbon\CarbonImmutable;
use Functional\Inspection\Events\DamageChanged;
use Functional\Inspection\Exceptions\DamageAlreadyResolvedException;
use Functional\Inspection\Models\Damage;
use Illuminate\Support\Facades\DB;

class ResolveDamage
{
    public function __construct(private readonly CountUnresolvedDamages $countUnresolvedDamages) {}

    public function handle(Damage $damage, User $resolver): Damage
    {
        DB::transaction(function () use ($damage, $resolver): void {
            $lockedDamage = Damage::query()->whereKey($damage->id)->lockForUpdate()->firstOrFail();

            if ($lockedDamage->isResolved()) {
                throw DamageAlreadyResolvedException::make();
            }

            $lockedDamage->update(['resolved_by' => $resolver->id, 'resolved_at' => CarbonImmutable::now()]);
        });

        DamageChanged::dispatch($damage->reservation_id, $this->countUnresolvedDamages->for($damage->reservation_id));

        return $damage->refresh();
    }
}
