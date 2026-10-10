<?php

namespace Functional\Inspection\Actions;

use Functional\Booking\Models\Reservation;
use Functional\Fleet\Models\MachineCategory;
use Functional\Inspection\Models\CategoryView;

class ResolveRequiredViews
{
    /**
     * @return list<string>
     */
    public function forReservation(Reservation $reservation): array
    {
        return $this->forCategory($reservation->machine->category);
    }

    /**
     * @return list<string>
     */
    public function forCategory(MachineCategory $category): array
    {
        $categoryLabels = CategoryView::query()
            ->whereBelongsTo($category, 'category')
            ->orderBy('position')
            ->pluck('label')
            ->all();

        return $categoryLabels !== [] ? array_values($categoryLabels) : $this->defaultLabels();
    }

    /**
     * @return list<string>
     */
    public function defaultLabels(): array
    {
        /** @var list<string> $defaultViewKeys */
        $defaultViewKeys = config('inspection.default_views');

        return array_map(fn (string $viewKey): string => __("inspection::views.default.{$viewKey}"), $defaultViewKeys);
    }
}
