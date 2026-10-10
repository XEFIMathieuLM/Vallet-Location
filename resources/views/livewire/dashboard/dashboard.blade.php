<div class="flex flex-col gap-8">
    <x-page-heading :title="__('dashboard.title')" />

    @can(\Functional\Booking\Access\BookingPermission::ManageReservations->value)
        <livewire:dashboard.day-operations :agency-id="$this->agencyId" />
    @endcan

    @unless ($this->hasAnySection)
        <x-empty-state :heading="__('dashboard.empty.heading')" :description="__('dashboard.empty.description')" />
    @endunless
</div>
