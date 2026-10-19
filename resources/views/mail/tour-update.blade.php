<x-mail::message>
# {{ $organizationName }}

{{ __('tour_notifications.body_'.$event) }}

{{ __('tour_notifications.disclaimer') }}

[{{ __('tour_notifications.unsubscribe') }}]({{ $unsubscribeUrl }})
</x-mail::message>
