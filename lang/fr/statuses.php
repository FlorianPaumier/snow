<?php

declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| Messages de statut (français)
|--------------------------------------------------------------------------
|
| Messages flash affichés via la prop `status` ou en toast après les
| actions utilisateur.
|
*/

return [
    'contract_created' => 'Contrat créé.',
    'contract_updated' => 'Contrat mis à jour.',
    'contract_deleted' => 'Contrat supprimé.',
    'payment_marked' => 'Versement encaissé.',
    'payment_unmarked' => 'Encaissement annulé.',
    'payment_reminder_sent' => 'Rappel envoyé par courriel.',
    'client_created' => 'Client créé.',
    'client_updated' => 'Client mis à jour.',
    'client_deleted' => 'Client supprimé.',
    'client_anonymized' => 'Client anonymisé. Les renseignements personnels ont été effacés; les dossiers financiers sont conservés à des fins de reddition de comptes.',
    'profile_updated' => 'Profil mis à jour.',
    'password_updated' => 'Mot de passe mis à jour.',
    'reset_link_sent' => 'Un lien de réinitialisation sera envoyé si le compte existe.',
    'plan_limit' => 'Votre forfait :plan permet :count :resource. Passez à un forfait supérieur pour en ajouter.',
    'read_only_blocked' => 'Votre essai est terminé. Passez à un forfait payant pour continuer à gérer vos données — tout reste consultable en attendant.',
    'unlock_used' => 'Le déblocage de 24 heures a déjà été utilisé pour cette organisation.',
    'unlock_granted' => 'Accès complet rétabli pendant 24 heures.',
    'resource_clients' => 'clients',
    'resource_users' => 'utilisateurs',
    'user_created' => 'Utilisateur créé.',
    'user_updated' => 'Utilisateur mis à jour.',
    'user_deleted' => 'Utilisateur supprimé.',
    'invitation_sent' => 'Invitation envoyée.',
    'invitation_accepted' => 'Mot de passe défini. Vous pouvez maintenant vous connecter.',
    'organization_updated' => 'Organisation mise à jour.',
    'organization_transferred' => 'Propriété transférée.',
    'organization_owner_delete' => 'Le propriétaire de l’organisation ne peut pas être supprimé. Transférez d’abord la propriété.',
    'organization_owner_role' => 'Le propriétaire de l’organisation doit rester administrateur. Transférez d’abord la propriété.',
    'document_pdf_generating' => 'Le PDF est en cours de génération. Réessayez dans un instant.',
];
