<section class="flex flex-col gap-6">
    <div class="flex flex-col gap-2">
        <x-page-heading :title="__('inspection::damages.comparison.title', ['reference' => $reservation->machine->reference])" />
        <flux:text>{{ $reservation->customer->name }}</flux:text>
    </div>

    <x-loading-hint />

    @error('refusal')
        <flux:callout variant="danger" icon="x-circle" :heading="$message" />
    @enderror

    <div class="flex flex-col gap-4">
        @foreach ($views as $view)
            <div wire:key="comparison-view-{{ $view->id }}" class="flex flex-col gap-2 rounded-xl border border-zinc-200 p-4 dark:border-zinc-700">
                <x-section-heading :title="$view->label" />
                <div class="grid gap-4 md:grid-cols-2">
                    @foreach (\Functional\Inspection\Enums\InspectionStep::cases() as $step)
                        <div class="flex flex-col gap-2">
                            <flux:text variant="strong">{{ ucfirst($step->label()) }}</flux:text>
                            @forelse ($view->photos->filter(fn ($photo) => $photo->step === $step) as $photo)
                                <figure wire:key="comparison-photo-{{ $photo->id }}" class="flex flex-col gap-2">
                                    <a href="{{ route('inspection.photo-file', [$photo, 'display']) }}" target="_blank" rel="noopener">
                                        <img src="{{ route('inspection.photo-file', [$photo, 'display']) }}" alt="{{ $view->label }} – {{ $step->label() }}" class="max-h-80 w-full rounded-lg object-contain bg-zinc-100 dark:bg-zinc-900" loading="lazy" />
                                    </a>
                                    <figcaption>
                                        <flux:text size="sm">
                                            {{ __('inspection::damages.comparison.taken', ['date' => $photo->created_at->format('d/m/Y H:i'), 'author' => $photo->session->author->name]) }}
                                        </flux:text>
                                    </figcaption>
                                </figure>
                            @empty
                                <flux:badge color="red" size="sm" class="self-start">{{ __('inspection::phone.view.missing') }}</flux:badge>
                            @endforelse
                        </div>
                    @endforeach
                </div>
            </div>
        @endforeach
    </div>

    @can(\Functional\Inspection\Access\InspectionPermission::ManageDamages->value)
        <form wire:submit="report" class="flex flex-col gap-4 rounded-xl border border-zinc-200 p-4 dark:border-zinc-700">
            <x-section-heading :title="__('inspection::damages.report.title')" />
            <flux:select wire:model="reservationViewId" :label="__('inspection::damages.report.view')" :placeholder="__('inspection::damages.report.choose_view')">
                @foreach ($views as $view)
                    <flux:select.option :value="$view->id">{{ $view->label }}</flux:select.option>
                @endforeach
            </flux:select>
            <flux:textarea wire:model="comment" :label="__('inspection::damages.report.comment')" rows="3" />
            <div>
                <flux:button type="submit" variant="primary">{{ __('inspection::damages.report.submit') }}</flux:button>
            </div>
        </form>
    @endcan

    @if ($damages->isNotEmpty())
        <div class="flex flex-col gap-4">
            <x-section-heading :title="__('inspection::damages.comparison.damages')" />
            @foreach ($damages as $damage)
                <div wire:key="damage-{{ $damage->id }}" class="flex flex-wrap items-center justify-between gap-2 rounded-lg border border-zinc-200 p-4 dark:border-zinc-700">
                    <div class="flex flex-col gap-2">
                        <flux:text variant="strong">{{ $damage->view->label }} : {{ $damage->comment }}</flux:text>
                        <flux:text size="sm">{{ __('inspection::damages.reported', ['author' => $damage->reporter->name, 'date' => $damage->reported_at->format('d/m/Y H:i')]) }}</flux:text>
                        @if ($damage->isResolved())
                            <flux:text size="sm">{{ __('inspection::damages.resolved', ['author' => $damage->resolver?->name, 'date' => $damage->resolved_at?->format('d/m/Y H:i')]) }}</flux:text>
                        @endif
                    </div>
                    @unless ($damage->isResolved())
                        @include('inspection::partials.damage-actions', ['damage' => $damage])
                    @endunless
                </div>
            @endforeach
        </div>
    @endif
</section>
