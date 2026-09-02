<x-mail::message>
    # New contact inquiry

    <x-mail::panel>
    **Name:** {{ $inquiry->name }}<br>
    **Email:** {{ $inquiry->email }}<br>

    @if ($inquiry->company)
    **Company:** {{ $inquiry->company }}<br>
    @endif

    @if ($inquiry->fleetSize)
    **Fleet size:** {{ $inquiry->fleetSize }}
    @endif
    </x-mail::panel>

    <x-mail::panel>
    {{ $inquiry->message }}
    </x-mail::panel>

    Thanks,<br>
    {{ config('app.name') }}
</x-mail::message>
