<x-mail::message>
# {{ __('portal::mail.verify.heading') }}

{{ __('portal::mail.greeting', ['name' => $accountName]) }}

{{ __('portal::mail.verify.intro') }}

<x-mail::button :url="$verificationUrl">
{{ __('portal::mail.verify.action') }}
</x-mail::button>

{{ __('portal::mail.link_lifetime', ['minutes' => $lifetimeMinutes]) }}

{{ __('portal::mail.verify.ignore') }}

{{ __('portal::mail.signature') }}
</x-mail::message>
