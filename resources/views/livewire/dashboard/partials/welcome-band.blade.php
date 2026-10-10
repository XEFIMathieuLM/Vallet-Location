<div class="grid lg:grid-cols-[minmax(0,1fr)_minmax(0,32rem)]">
    <div class="flex flex-col justify-end gap-5 bg-zinc-50 p-6 sm:p-10 dark:bg-zinc-900">
        <p class="eyebrow text-zinc-500 first-letter:uppercase dark:text-white/72">{{ $today->locale('fr')->isoFormat('dddd D MMMM') }}</p>
        <p class="display-xl text-zinc-950 dark:text-white">{{ __('dashboard.welcome.title') }}</p>
        <p class="max-w-xl text-lg text-zinc-800 dark:text-white/80">{{ \Illuminate\Support\Str::ucfirst(__('dashboard.welcome.summary', [
            'departures' => trans_choice('dashboard.welcome.departures', $departures->total),
            'returns' => trans_choice('dashboard.welcome.returns', $returns->total),
            'late' => trans_choice('dashboard.welcome.late', $lateReturns->total),
        ])) }}</p>
    </div>
    <div aria-hidden="true" class="relative hidden min-h-64 overflow-hidden bg-black text-white sm:block dark:border dark:border-white/16">
        <div class="absolute inset-x-10 bottom-10 flex items-end gap-6">
            <x-fleet::pictogram category="nacelle" class="w-3/5 max-w-80" />
            <x-fleet::pictogram category="mini-pelle" class="w-1/3 max-w-44" />
        </div>
        <div class="absolute inset-x-10 bottom-10 h-[3px] bg-white"></div>
    </div>
</div>
