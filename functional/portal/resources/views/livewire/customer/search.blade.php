<div class="flex flex-col gap-6">
    <x-page-heading :title="__('portal::search.title')" />

    <div class="grid gap-4 md:grid-cols-4">
        <flux:select wire:model.live="categoryId" :label="__('portal::search.category')" :placeholder="__('portal::search.choose')">
            @foreach ($categories as $category)
                <flux:select.option :value="$category->id">{{ $category->name }}</flux:select.option>
            @endforeach
        </flux:select>

        <flux:select wire:model.live="agencyId" :label="__('portal::search.agency')" :placeholder="__('portal::search.choose')">
            @foreach ($agencies as $agency)
                <flux:select.option :value="$agency->id">{{ $agency->name }}</flux:select.option>
            @endforeach
        </flux:select>

        <flux:input type="date" wire:model.live="startDate" :label="__('portal::search.start_date')" :min="now()->toDateString()" />
        <flux:input type="date" wire:model.live="endDate" :label="__('portal::search.end_date')" :min="$startDate !== '' ? $startDate : now()->toDateString()" />
    </div>

    <flux:text>{{ __('portal::search.price_notice') }}</flux:text>

    <x-loading-hint />

    @if (! $hasCriteria)
        <flux:callout icon="information-circle" :heading="__('portal::search.criteria_required')" />
    @elseif ($offers === [])
        <x-empty-state :heading="__('portal::search.empty')" :description="__('portal::search.empty_help')" />
    @else
        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3" wire:loading.class="opacity-50">
            @foreach ($offers as $offer)
                <flux:card wire:key="offer-{{ $offer->machineId }}" class="flex flex-col gap-3">
                    <div>
                        <flux:heading size="lg">{{ $offer->reference }}</flux:heading>
                        <flux:text>{{ $offer->categoryName }} · {{ __('portal::search.at_agency', ['agency' => $offer->agencyName]) }}</flux:text>
                    </div>
                    <div>@include('portal::partials.indicative-price', ['dailyPriceCents' => $offer->dailyPriceCents])</div>
                    <div class="mt-auto">
                        <flux:button size="sm" variant="primary" icon="paper-airplane" wire:click="requestMachine({{ $offer->machineId }})">
                            {{ __('portal::search.request') }}
                        </flux:button>
                    </div>
                </flux:card>
            @endforeach
        </div>
    @endif

    <flux:modal name="send-request" class="md:w-[32rem]">
        @if ($requestedMachineId !== null)
            <livewire:portal.send-request-form :machine-id="$requestedMachineId" :start-date="$startDate" :end-date="$endDate" :key="'send-request-'.$requestedMachineId.'-'.$startDate.'-'.$endDate" />
        @endif
    </flux:modal>
</div>
