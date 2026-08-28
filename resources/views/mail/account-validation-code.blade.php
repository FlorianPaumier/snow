<x-mail::message>
# Validate your account

Use the 6-digit code below to validate your account. The code expires in 15 minutes.

<x-mail::panel>
# {{ $code }}
</x-mail::panel>

If you didn't create an account, no further action is required.

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
