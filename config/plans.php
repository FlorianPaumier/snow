<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | Subscription Plans
    |--------------------------------------------------------------------------
    |
    | The SaaS plans sold on the marketing pricing page. Each plan maps its
    | monthly and annual Stripe price IDs along with the resource limits
    | enforced by the application. Tier names must match the pricing page
    | copy (Starter / Pro / Fleet). Prices live in Stripe; the amounts on
    | the marketing page are display copy only.
    |
    */

    'starter' => [
        'name' => 'Starter',
        'prices' => [
            'monthly' => env('STRIPE_PRICE_STARTER_MONTHLY'),
            'annual' => env('STRIPE_PRICE_STARTER_ANNUAL'),
        ],
        'limits' => [
            'maxUsers' => 2,
            'maxClients' => 25,
        ],
    ],

    'pro' => [
        'name' => 'Pro',
        'prices' => [
            'monthly' => env('STRIPE_PRICE_PRO_MONTHLY'),
            'annual' => env('STRIPE_PRICE_PRO_ANNUAL'),
        ],
        'limits' => [
            'maxUsers' => 15,
            'maxClients' => 150,
        ],
    ],

    'fleet' => [
        'name' => 'Fleet',
        'prices' => [
            'monthly' => env('STRIPE_PRICE_FLEET_MONTHLY'),
            'annual' => env('STRIPE_PRICE_FLEET_ANNUAL'),
        ],
        'limits' => [
            'maxUsers' => null, // Unlimited...
            'maxClients' => null,
        ],
    ],

];
