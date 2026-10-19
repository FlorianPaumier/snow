<x-mail::message>
    # {{ __('invitations.contact_title') }}

    <x-mail::panel>
    **{{ __('invitations.name') }}:** {{ $inquiry->name }}<br>
    **{{ __('invitations.email') }}:** {{ $inquiry->email }}<br>

    @if ($inquiry->company)
    **{{ __('invitations.company') }}:** {{ $inquiry->company }}<br>
    @endif

    @if ($inquiry->fleetSize)
    **{{ __('invitations.fleet') }}:** {{ $inquiry->fleetSize }}
    @endif
    </x-mail::panel>

    <x-mail::panel>
    {{ $inquiry->message }}
    </x-mail::panel>

    {{ __('invitations.thanks') }}<br>
    {{ config('app.name') }}
</x-mail::message>
