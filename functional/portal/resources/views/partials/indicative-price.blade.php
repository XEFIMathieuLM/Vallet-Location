@if ($dailyPriceCents !== null)
    <span class="font-medium text-zinc-800 dark:text-white">{{ __('portal::search.price_from', ['amount' => $priceFormatter->amount($dailyPriceCents)]) }}</span>
@else
    <span class="text-zinc-500 dark:text-zinc-400">{{ __('portal::search.price_on_request') }}</span>
@endif
