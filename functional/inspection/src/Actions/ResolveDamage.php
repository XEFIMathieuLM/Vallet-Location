<?php

namespace Functional\Inspection\Actions;

use App\Models\User;
use Carbon\CarbonImmutable;
use Functional\Inspection\Events\DamageChanged;
use Functional\Inspection\Exceptions\DamageAlreadyResolvedException;
use Functional\Inspection\Models\Damage;
use Functional\Inspection\Support\InspectionHistory;
use Illuminate\Support\Facades\DB;

class ResolveDamage
{
    public function __construct(
        private readonly CountUnresolvedDamages $countUnresolvedDamages,
        private readonly InspectionHistory $inspectionHistory,
    ) {}

    public function handle(Damage $damage, User $resolver): Damage
    {
        DB::transaction(function () use ($damage, $resolver): void {
            $lockedDamage = Damage::query()->whereKey($damage->id)->lockForUpdate()->firstOrFail();

            if ($lockedDamage->isResolved()) {
                throw DamageAlreadyResolvedException::make();
            }

            $lockedDamage->update(['resolved_by' => $resolver->id, 'resolved_at' => CarbonImmutable::now()]);

            $this->inspectionHistory->record($lockedDamage->reservation, 'damage.resolved', $resolver, ['damage_id' => $lockedDamage->id]);
        });

        DamageChanged::dispatch($damage->reservation_id, $this->countUnresolvedDamages->for($damage->reservation_id));

        return $damage->refresh();
    }
}
