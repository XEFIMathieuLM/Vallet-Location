<x-mail::message>
# {{ __('certification::mail.heading') }}

{{ __('certification::mail.greeting', ['name' => $customerName]) }}

{{ __('certification::mail.intro') }}

<x-mail::table>
| | |
| :-- | :-- |
| {{ __('certification::mail.machine') }} | {{ $machineReference }} ({{ $machineCategory }}) |
| {{ __('certification::mail.period') }} | {{ __('certification::mail.period_value', ['start' => $startDate, 'end' => $endDate]) }} |
| {{ __('certification::mail.agency') }} | {{ $agencyName }} |
| {{ __('certification::mail.verified_on') }} | {{ $verifiedOn }} |
| {{ __('certification::mail.due_on') }} | {{ $dueOn }} |
</x-mail::table>

{{ __('certification::mail.attachment') }}

{{ __('certification::mail.signature') }}
</x-mail::message>
