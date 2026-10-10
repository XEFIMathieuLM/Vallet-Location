<div class="space-y-1">
    <flux:text class="font-medium">{{ __('deposit::section.damages_to_settle') }}</flux:text>
    <ul class="list-disc space-y-1 ps-5 text-sm">
        @foreach ($damages as $damage)
            <li>{{ $damage->view->label }} : {{ $damage->comment }}</li>
        @endforeach
    </ul>
</div>
