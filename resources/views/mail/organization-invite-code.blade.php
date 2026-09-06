<x-mail::message>
# Your invitation code

You've been invited to set up your company on {{ config('app.name') }}.

Use the code below when creating your account. The code can be used once and expires on **{{ $expiresAt }}**.

<x-mail::panel>
{{ $code }}
</x-mail::panel>

<x-mail::button :url="$url">
Create your account
</x-mail::button>

If you weren't expecting this invitation, no further action is required.

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
