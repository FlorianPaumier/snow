<?php

declare(strict_types=1);

return [
    'optimization_not_configured' => 'La clé openrouteservice doit être configurée pour optimiser une tournée.',
    'optimization_unavailable' => 'L’optimisation est temporairement indisponible. Réessayez plus tard ; l’ordre actuel est conservé.',
    'optimization_invalid_response' => 'Le service n’a pas retourné une tournée complète et valide.',
    'invalid_clients' => 'La liste des clients est invalide.',
    'cannot_start' => 'Cette tournée ne peut pas être démarrée.',
    'already_active' => 'Terminez votre tournée en cours avant d’en débuter une autre.',
    'cannot_finish' => 'Débutez la tournée avant de la terminer.',
    'order_locked' => 'La tournée a déjà commencé ou a été modifiée. Actualisez la page.',
    'invalid_order' => 'L’ordre doit contenir exactement les clients de cette tournée.',
    'address_not_found' => 'Adresse introuvable ou trop imprécise : :address. Corrigez-la avant de recréer la tournée.',
    'departure_required' => 'Une adresse de départ est nécessaire pour optimiser la tournée.',
    'too_many_stops' => 'Cette intégration accepte au maximum :count clients par optimisation. La tournée manuelle reste disponible.',
];
