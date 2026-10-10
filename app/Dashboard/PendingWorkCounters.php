<?php

namespace App\Dashboard;

use BackedEnum;
use Carbon\CarbonImmutable;
use Closure;
use Functional\Accounts\Access\AccountsPermission;
use Functional\Accounts\Queries\MissingPurchaseOrders;
use Functional\Billing\Enums\BillingPermission;
use Functional\Billing\Queries\TransmissionsToHandle;
use Functional\Certification\Enums\CertificationPermission;
use Functional\Certification\Queries\CertificatesToHandle;
use Functional\Deposit\Access\DepositPermission;
use Functional\Deposit\Queries\PendingDeposits;
use Functional\Inspection\Access\InspectionPermission;
use Functional\Inspection\Queries\DamagesToHandle;
use Functional\Sales\Access\SalesPermission;
use Functional\Sales\Queries\OverdueSales;
use Illuminate\Contracts\Auth\Access\Authorizable;

final class PendingWorkCounters
{
    /**
     * @return list<PendingWorkCounter>
     */
    public function forUser(Authorizable $user): array
    {
        $counters = [];

        foreach ($this->definitions() as $counterKey => [$permission, $url, $countItems]) {
            if ($user->can((string) $permission->value)) {
                $counters[] = new PendingWorkCounter($counterKey, $countItems(), $url);
            }
        }

        return $counters;
    }

    /**
     * @return array<string, array{BackedEnum, string, Closure(): int}>
     */
    private function definitions(): array
    {
        return [
            'transmissions' => [BillingPermission::Manage, route('billing.transmissions'), fn (): int => app(TransmissionsToHandle::class)->query()->count()],
            'certificates' => [CertificationPermission::Manage, route('certification.certificates'), fn (): int => app(CertificatesToHandle::class)->query()->count()],
            'deposits' => [DepositPermission::ManageDeposits, route('deposit.pending.index'), fn (): int => app(PendingDeposits::class)->query()->count()],
            'purchase_orders' => [AccountsPermission::ManagePurchaseOrders, route('accounts.missing-purchase-orders'), fn (): int => app(MissingPurchaseOrders::class)->query()->count()],
            'damages' => [InspectionPermission::ManageDamages, route('inspection.damages'), fn (): int => app(DamagesToHandle::class)->query()->count()],
            'overdue_sales' => [SalesPermission::Manage, route('sales.index', ['statut' => 'reserved']), fn (): int => app(OverdueSales::class)->query(CarbonImmutable::today())->count()],
        ];
    }
}
