<?php

declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| Domain-specific validation and status messages (French)
|--------------------------------------------------------------------------
|
| Messages propres au domaine déneigement : invariants des versements,
| conflits de saison et statuts d'encaissement.
|
*/

return [
    'totals_mismatch' => 'Le total des versements doit correspondre au total des services.',
    'slot_amount_locked' => 'Ce versement est encaissé et son montant ne peut plus être modifié.',
    'duplicate_season' => 'Ce client a déjà un contrat pour cette saison.',
    'already_marked' => 'Ce versement est déjà marqué comme encaissé.',
    'not_marked' => "Ce versement n'est pas marqué comme encaissé.",
    'delete_with_paid' => 'Ce contrat a des versements encaissés et ne peut pas être supprimé.',
    'invalid_code' => 'Le code fourni est invalide ou a expiré.',
];
