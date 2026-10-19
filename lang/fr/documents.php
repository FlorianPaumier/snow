<?php

declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| Document strings (français)
|--------------------------------------------------------------------------
|
| Le sommaire de contrat imprimable : page HTML autonome et source du
| PDF généré par Gotenberg. Doit rester lisible par un client.
|
*/

return [
    'method_stripe' => 'Stripe',
    'reversed_amount' => 'Montant remboursé ou contesté',
    'page_title' => 'Sommaire de contrat — :client :season',
    'document_title' => 'Sommaire de contrat',
    'issued_on' => 'Émis le :date',
    'print' => 'Imprimer',

    'client' => 'Client',
    'client_number' => 'Nº client',
    'service_address' => 'Adresse de service',
    'contract_details' => 'Entente',
    'season' => 'Saison',
    'contract_date' => 'Date du contrat',

    'services' => 'Services',
    'service' => 'Service',
    'amount' => 'Montant',
    'total_services' => 'Total des services',

    'installments' => 'Versements',
    'installment' => 'Versement',
    'status' => 'Statut',
    'paid_on' => 'Payé le :date',
    'pending' => 'À payer',

    'total' => 'Total',
    'paid_to_date' => 'Payé à ce jour',
    'balance_due' => 'Solde dû',

    'generated_on' => 'Document généré le :date par :organization',

    // Reçu de paiement...
    'receipt_page_title' => 'Reçu de paiement — :client :season',
    'receipt_title' => 'Reçu de paiement',
    'receipt_payment' => 'Détails du paiement',
    'receipt_contract' => 'Contrat',
    'receipt_method' => 'Mode de paiement',
    'receipt_paid_on' => 'Payé le',
    'amount_paid' => 'Montant payé',

    'slot_deposit' => 'Dépôt',
    'slot_first' => '1er versement',
    'slot_second' => '2e versement',
    'slot_balance' => 'Solde',

    'method_cash' => 'Espèces',
    'method_debit' => 'Débit',
    'method_virement' => 'Virement',
    'method_cheque' => 'Chèque',
];
