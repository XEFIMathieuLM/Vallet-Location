<div class="flex flex-col gap-8">
    <x-page-heading :title="__('dashboard.title')" />

    @unless ($this->hasAnySection)
        <x-empty-state :heading="__('dashboard.empty.heading')" :description="__('dashboard.empty.description')" />
    @endunless
</div>
