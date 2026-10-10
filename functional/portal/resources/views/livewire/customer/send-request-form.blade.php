<form wire:submit="send" class="flex flex-col gap-5">
    <flux:heading size="lg">{{ __('portal::search.request_title') }}</flux:heading>

    <dl class="grid grid-cols-[auto_1fr] gap-x-4 gap-y-1 text-sm">
        <dt class="text-zinc-500">{{ __('portal::search.machine') }}</dt>
        <dd>{{ $machine->reference }} ({{ $machine->category->name }})</dd>
        <dt class="text-zinc-500">{{ __('portal::search.period') }}</dt>
        <dd>{{ __('portal::search.period_value', ['start' => \Carbon\CarbonImmutable::parse($startDate)->format('d/m/Y'), 'end' => \Carbon\CarbonImmutable::parse($endDate)->format('d/m/Y')]) }}</dd>
        <dt class="text-zinc-500">{{ __('portal::search.pickup_agency') }}</dt>
        <dd>{{ $machine->agency->name }}</dd>
        <dt class="text-zinc-500">{{ __('portal::search.price') }}</dt>
        <dd>@include('portal::partials.indicative-price')</dd>
    </dl>

    <flux:textarea wire:model="comment" :label="__('portal::search.comment')" :description="__('portal::search.comment_hint')" rows="3" />

    @error('refusal')
        <flux:callout variant="danger" icon="exclamation-triangle" :heading="$message" />
    @enderror

    <flux:callout icon="information-circle" :heading="__('portal::search.request_notice')" />

    <div class="flex justify-end gap-2">
        <flux:modal.close>
            <flux:button variant="ghost">{{ __('portal::search.cancel') }}</flux:button>
        </flux:modal.close>
        <flux:button type="submit" variant="primary" data-test="portal-send-request">{{ __('portal::search.send') }}</flux:button>
    </div>
</form>
