<x-mail::message>
# {{ __('portal::mail.decision.refused.heading') }}

{{ __('portal::mail.greeting', ['name' => $accountName]) }}

{{ __('portal::mail.decision.refused.intro') }}

<x-mail::table>
| | |
| :-- | :-- |
| {{ __('portal::mail.decision.machine') }} | {{ $machineReference }} ({{ $machineCategory }}) |
| {{ __('portal::mail.decision.period') }} | {{ __('portal::mail.decision.period_value', ['start' => $startDate, 'end' => $endDate]) }} |
| {{ __('portal::mail.decision.agency') }} | {{ $agencyName }}@if ($agencyAddress !== null) — {{ $agencyAddress }}@endif |
@if ($refusalReason !== null)
| {{ __('portal::mail.decision.reason') }} | {{ $refusalReason }} |
@endif
</x-mail::table>

{{ __('portal::mail.decision.refused.outro') }}

<x-mail::button :url="route('portal.requests')">
{{ __('portal::mail.decision.action') }}
</x-mail::button>

{{ __('portal::mail.signature') }}
</x-mail::message>
