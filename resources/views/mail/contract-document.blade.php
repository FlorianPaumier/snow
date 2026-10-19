<x-mail::message>
# {{ $contract->organization->name }}

{{ __('contract_mail.intro', ['client' => $contract->client->displayName(), 'season' => $contract->season]) }}

{{ __('contract_mail.attachment') }}
</x-mail::message>
