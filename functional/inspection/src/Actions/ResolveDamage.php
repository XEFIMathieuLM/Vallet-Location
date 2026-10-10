<?php

namespace Functional\Inspection\Actions;

use Carbon\CarbonImmutable;
use Functional\Fleet\Contracts\AgencyMember;
use Functional\Inspection\Events\DamageChanged;
use Functional\Inspection\Exceptions\DamageAlreadyResolvedException;
use Functional\Inspection\History\InspectionHistory;
use Functional\Inspection\History\InspectionHistoryEvent;
use Functional\Inspection\Models\Damage;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class ResolveDamage
{
    public function __construct(
        private readonly CountUnresolvedDamages $countUnresolvedDamages,
        private readonly InspectionHistory $inspectionHistory,
    ) {}

    public function handle(Damage $damage, Model&AgencyMember $resolver): Damage
    {
        DB::transaction(function () use ($damage, $resolver): void {
            $lockedDamage = Damage::query()->whereKey($damage->id)->lockForUpdate()->firstOrFail();

            if ($lockedDamage->isResolved()) {
                throw DamageAlreadyResolvedException::for($lockedDamage);
            }

            $lockedDamage->update(['resolved_by' => $resolver->getKey(), 'resolved_at' => CarbonImmutable::now()]);

            $this->inspectionHistory->record($lockedDamage->reservation, InspectionHistoryEvent::DamageResolved, $resolver, ['damage_id' => $lockedDamage->id]);
        });

        DamageChanged::dispatch($damage->reservation_id, $this->countUnresolvedDamages->for($damage->reservation));

        return $damage->refresh();
    }
}
