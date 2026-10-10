<div class="flex flex-col">
    <x-portal::hero :title="__('portal::search.title')">
        <div class="grid gap-6 md:grid-cols-4">
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
    </x-portal::hero>

    <div class="mx-auto flex w-full max-w-[90rem] flex-col gap-8 px-4 py-12 sm:px-10">
        <x-loading-hint />

        @if (! $hasCriteria)
            <flux:callout icon="information-circle" :heading="__('portal::search.criteria_required')" />
        @elseif ($offers === [])
            <x-empty-state :heading="__('portal::search.empty')" :description="__('portal::search.empty_help')" />
        @else
            <x-section-heading :title="trans_choice('portal::search.results_count', count($offers))" class="border-b-2 border-zinc-950 pb-3" />

            <div class="grid gap-x-6 gap-y-10 sm:grid-cols-2 lg:grid-cols-3" wire:loading.class="opacity-50">
                @foreach ($offers as $offer)
                    <article wire:key="offer-{{ $offer->machineId }}" class="flex flex-col bg-white" data-machine-card>
                        <div class="flex aspect-[16/9] items-end bg-zinc-50 p-6">
                            <x-fleet::pictogram :category="$offer->categoryName" class="h-3/4 max-w-full" />
                        </div>
                        <div class="flex flex-1 flex-col gap-4 pt-6">
                            <div class="flex flex-col gap-2">
                                <h2 class="heading-lg" data-flux-heading>{{ $offer->reference }}</h2>
                                <flux:text>{{ $offer->categoryName }} · {{ __('portal::search.at_agency', ['agency' => $offer->agencyName]) }}</flux:text>
                            </div>
                            <p>@include('portal::partials.indicative-price', ['dailyPriceCents' => $offer->dailyPriceCents])</p>
                            <flux:button variant="filled" icon-trailing="arrow-right" class="mt-auto w-full justify-between!" wire:click="requestMachine({{ $offer->machineId }})">
                                {{ __('portal::search.request') }}
                            </flux:button>
                        </div>
                    </article>
                @endforeach
            </div>
        @endif
    </div>

    <flux:modal name="send-request" class="md:w-[32rem]">
        @if ($requestedMachineId !== null)
            <livewire:portal.send-request-form :machine-id="$requestedMachineId" :start-date="$startDate" :end-date="$endDate" :key="'send-request-'.$requestedMachineId.'-'.$startDate.'-'.$endDate" />
        @endif
    </flux:modal>
</div>
