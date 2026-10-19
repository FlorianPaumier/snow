<x-mail::message>
# {{ __('invitations.code_subject') }}

{{ __('invitations.code_intro', ['app' => config('app.name')]) }}

{{ __('invitations.code_body', ['date' => $expiresAt]) }}

<x-mail::panel>
{{ $code }}
</x-mail::panel>

<x-mail::button :url="$url">
{{ __('invitations.create') }}
</x-mail::button>

{{ __('invitations.unexpected') }}

{{ __('invitations.thanks') }}<br>
{{ config('app.name') }}
</x-mail::message>
