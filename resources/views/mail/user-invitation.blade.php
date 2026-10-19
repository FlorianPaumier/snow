<x-mail::message>
# {{ __('invitations.user_title') }}

{{ __($inviterName ? 'invitations.user_intro' : 'invitations.user_intro_without_inviter', ['inviter' => $inviterName, 'organization' => $organizationName, 'app' => config('app.name')]) }}

{{ __('invitations.user_body') }}

<x-mail::button :url="$url">
{{ __('invitations.password') }}
</x-mail::button>

{{ __('invitations.unexpected') }}

{{ __('invitations.thanks') }}<br>
{{ config('app.name') }}
</x-mail::message>
