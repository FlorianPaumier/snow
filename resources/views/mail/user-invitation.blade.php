<x-mail::message>
# You've been invited

@if ($inviterName)
{{ $inviterName }} has invited you to join **{{ $organizationName }}** on {{ config('app.name') }}.
@else
You have been invited to join **{{ $organizationName }}** on {{ config('app.name') }}.
@endif

Choose your own password with the button below to activate your account. The link expires in 72 hours.

<x-mail::button :url="$url">
Set your password
</x-mail::button>

If you weren't expecting this invitation, no further action is required.

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
