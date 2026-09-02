<x-mail::message>
# Bonjour {{ $reminder['clientName'] }},

Nous souhaitons vous rappeler le versement suivant, toujours en attente de règlement :

<x-mail::panel>
**{{ $reminder['slotLabel'] }}** — {{ $reminder['amount'] }}<br>
Saison {{ $reminder['season'] }}<br>
Échéance : {{ $reminder['dueDate'] }} ({{ $reminder['duePhrase'] }})
</x-mail::panel>

Merci de nous faire parvenir le règlement dès que possible, ou de nous contacter si un arrangement est nécessaire.

Merci,<br>
{{ $reminder['organizationName'] }}
</x-mail::message>
