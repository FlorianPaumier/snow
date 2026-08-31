<x-mail::message>
# Confirm it's you

Use the 6-digit code below to complete your login. The code expires in 15 minutes.

<x-mail::panel>
# {{ $code }}
</x-mail::panel>

If you didn't attempt to log in to your account, no further action is required.

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
