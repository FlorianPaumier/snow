<?php

declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| Status messages (English)
|--------------------------------------------------------------------------
*/

return [
    'clients_import_success' => ':count client(s) imported.',
    'clients_import_empty' => 'The CSV file has no data rows.',
    'clients_import_invalid' => 'Fix the invalid rows before importing.',
    'contracts_import_success' => ':count contract(s) imported.',
    'contracts_import_invalid' => 'Fix the invalid rows before importing.',
    'users_import_success' => ':count user(s) imported.',
    'users_import_empty' => 'The CSV file has no data rows.',
    'users_import_invalid' => 'Fix the invalid rows before importing.',
    'users_import_fields' => [
        'name' => 'Name',
        'email' => 'Email address',
        'role' => 'Role (admin/worker/user)',
    ],
    'contract_created' => 'Contract created.',
    'contract_updated' => 'Contract updated.',
    'contract_deleted' => 'Contract deleted.',
    'payment_marked' => 'Payment collected.',
    'payment_unmarked' => 'Collection cancelled.',
    'payment_reminder_sent' => 'Reminder sent by email.',
    'client_created' => 'Client created.',
    'client_updated' => 'Client updated.',
    'client_deleted' => 'Client deleted.',
    'client_anonymized' => 'Client anonymized. Personal data was erased; financial records are kept for accountability.',
    'profile_updated' => 'Profile updated.',
    'password_updated' => 'Password updated.',
    'reset_link_sent' => 'A reset link will be sent if the account exists.',
    'plan_limit' => 'Your :plan plan allows :count :resource. Upgrade to Pro to add more.',
    'feature_locked' => 'Payment reminders are part of the Pro plan. Upgrade to automate your collections.',
    'trial_plan_note' => 'Your free trial has ended — you are now on the Free plan. Upgrade to Pro to add more clients and users.',
    'resource_clients' => 'clients',
    'resource_users' => 'users',
    'user_created' => 'User created.',
    'user_updated' => 'User updated.',
    'user_deleted' => 'User deleted.',
    'invitation_sent' => 'Invitation sent.',
    'invitation_accepted' => 'Password set. You can now sign in.',
    'organization_updated' => 'Organization updated.',
    'organization_transferred' => 'Ownership transferred.',
    'organization_owner_delete' => 'The organization owner cannot be deleted. Transfer ownership first.',
    'organization_owner_role' => 'The organization owner must remain an administrator. Transfer ownership first.',
    'document_pdf_generating' => 'The PDF is being generated. Try again in a moment.',
];
