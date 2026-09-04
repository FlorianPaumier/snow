<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | Personal Data Retention
    |--------------------------------------------------------------------------
    |
    | How many years after a client's last contract season their personal
    | data is kept before the scheduled clients:anonymize-expired command
    | erases it (Quebec Law 25 s. 22: destroy or anonymize once the purpose
    | is achieved, subject to legal retention). Six years matches the
    | record-keeping obligation for books and supporting documents; the
    | financial skeleton (client number, contracts, payments) is always
    | kept for accountability.
    |
    */

    // Cast to int: env() always yields strings from .env, and
    // config()->integer() throws on anything else.
    'client_retention_years' => (int) env('CLIENT_RETENTION_YEARS', 6),

];
