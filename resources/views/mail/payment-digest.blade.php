<x-mail::message>
# Suivi des paiements — {{ $digest['organizationName'] }}

@if ($digest['overdue'])
## En retard

| Client | Versement | Saison | Montant | Échéance |
|-|-|-|-|-|
@foreach ($digest['overdue'] as $row)
| {{ $row['clientName'] }} | {{ $row['slotLabel'] }} | {{ $row['season'] }} | {{ $row['amount'] }} | {{ $row['dueDate'] }} |
@endforeach
@else
Aucun versement en retard.
@endif

@if ($upcoming = $digest['upcoming'])
## À venir

| Client | Versement | Saison | Montant | Échéance |
|-|-|-|-|-|
@foreach ($upcoming as $row)
| {{ $row['clientName'] }} | {{ $row['slotLabel'] }} | {{ $row['season'] }} | {{ $row['amount'] }} | {{ $row['dueDate'] }} ({{ $row['duePhrase'] }}) |
@endforeach
@else
Aucun versement à venir dans les prochains jours.
@endif

Merci,<br>
{{ config('app.name') }}
</x-mail::message>
