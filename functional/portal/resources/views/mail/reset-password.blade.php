<x-mail::message>
# {{ __('portal::mail.reset.heading') }}

{{ __('portal::mail.greeting', ['name' => $accountName]) }}

{{ __('portal::mail.reset.intro') }}

<x-mail::button :url="$resetUrl">
{{ __('portal::mail.reset.action') }}
</x-mail::button>

{{ __('portal::mail.link_lifetime', ['minutes' => $lifetimeMinutes]) }}

{{ __('portal::mail.reset.ignore') }}

{{ __('portal::mail.signature') }}
</x-mail::message>
