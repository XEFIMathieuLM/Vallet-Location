<?php

namespace Functional\Inspection\Exceptions;

use Functional\Fleet\Exceptions\RefusalException;
use Functional\Inspection\Models\ReservationView;
use Illuminate\Support\Collection;

final class MissingPhotosException extends RefusalException
{
    /**
     * @param  Collection<int, ReservationView>  $missingViews
     */
    public static function for(Collection $missingViews): self
    {
        return new self(__('inspection::photos.refusals.missing_photos', [
            'views' => $missingViews->pluck('label')->implode(', '),
        ]));
    }
}
