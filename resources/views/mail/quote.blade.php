<x-mail::message>
# {{ $quote->organization_name }}

{{ __('quotes.mail_intro', ['name' => $quote->customer['name'], 'season' => $quote->season]) }}

<x-mail::button :url="$quote->reviewUrl()">
{{ __('quotes.review') }}
</x-mail::button>

{{ __('quotes.mail_expiry', ['date' => $quote->expires_at->toDateString()]) }}
</x-mail::message>
