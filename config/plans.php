<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | Subscription Plans
    |--------------------------------------------------------------------------
    |
    | The SaaS plans sold on the marketing pricing page. Free is the capped
    | default every organization falls back to — with no Stripe price of
    | its own, it is simply the absence of a valid subscription. Pro is
    | the single paid tier; its monthly and annual Stripe price IDs are
    | configured below. Amounts on the marketing page are display copy
    | only — prices live in Stripe (CAD, tax-exclusive: Stripe Tax adds
    | GST/QST at checkout).
    |
    | The "features" flags gate product features behind the paid tier
    | (payment reminders & notifications). The marketing comparison
    | table must stay in sync with these flags.
    |
    */

    'free' => [
        'name' => 'Free',
        'limits' => [
            'maxUsers' => 1,
            'maxClients' => 15,
        ],
        'features' => [
            'paymentReminders' => false,
        ],
    ],

    'pro' => [
        'name' => 'Pro',
        'prices' => [
            'monthly' => env('STRIPE_PRICE_PRO_MONTHLY'),
            'annual' => env('STRIPE_PRICE_PRO_ANNUAL'),
        ],
        'limits' => [
            'maxUsers' => null, // Unlimited...
            'maxClients' => null,
        ],
        'features' => [
            'paymentReminders' => true,
        ],
    ],

];
