<?php

namespace Functional\Portal\Access\Controls;

use Functional\Fleet\Access\Perimeters\GlobalPerimeter;
use Functional\Portal\Access\Perimeters\OwnAccountPerimeter;
use Functional\Portal\Access\PortalPermission;
use Functional\Portal\Models\CustomerAccount;
use Functional\Portal\Models\ReservationRequest;
use Illuminate\Contracts\Auth\Access\Authorizable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Lomkit\Access\Controls\Control;

class ReservationRequestControl extends Control
{
    /**
     * @var class-string<Model>
     */
    protected string $model = ReservationRequest::class;

    /**
     * @return Builder<ReservationRequest>
     */
    public static function ownedBy(CustomerAccount $account): Builder
    {
        /** @var Builder<ReservationRequest> $ownRequests */
        $ownRequests = (new self)->queried(ReservationRequest::query(), $account);

        return $ownRequests;
    }

    protected function perimeters(): array
    {
        return [
            OwnAccountPerimeter::new()
                ->allowed(fn (Model $user, string $method): bool => $user instanceof CustomerAccount)
                ->should(fn (Model $user, Model $reservationRequest): bool => $reservationRequest instanceof ReservationRequest && $reservationRequest->customer_account_id === $user->getKey())
                ->query(fn (Builder $query, Model $user): Builder => $query->where('customer_account_id', $user->getKey())),
            GlobalPerimeter::new()
                ->allowed(fn (Model $user, string $method): bool => $user instanceof Authorizable && ! $user instanceof CustomerAccount && $user->can(PortalPermission::HandleRequests->value))
                ->should(fn (Model $user, Model $reservationRequest): bool => true)
                ->query(fn (Builder $query, Model $user): Builder => $query),
        ];
    }
}
