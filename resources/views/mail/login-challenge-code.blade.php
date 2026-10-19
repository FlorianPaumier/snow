<x-mail::message>
# {{ __('invitations.login_title') }}

{{ __('invitations.login_body') }}

<x-mail::panel>
# {{ $code }}
</x-mail::panel>

{{ __('invitations.login_unexpected') }}

{{ __('invitations.thanks') }}<br>
{{ config('app.name') }}
</x-mail::message>
