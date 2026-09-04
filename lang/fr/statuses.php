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
    'plan_limit' => 'Votre forfait :plan permet :count :resource. Passez à Pro pour en ajouter.',
    'feature_locked' => 'Les rappels de paiement font partie du forfait Pro. Passez à Pro pour automatiser vos encaissements.',
    'trial_plan_note' => 'Votre essai gratuit est terminé — vous êtes maintenant sur le forfait gratuit. Passez à Pro pour ajouter plus de clients et d’utilisateurs.',
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
