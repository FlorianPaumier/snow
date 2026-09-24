<?php

declare(strict_types=1);

it('fails closed in production when the Stripe webhook secret is absent', function (): void {
    config([
        'app.env' => 'production',
        'cashier.webhook.secret' => null,
    ]);

    $this->postJson(route('cashier.webhook'), [])->assertServiceUnavailable();
});

it('fails closed in staging when the Stripe webhook secret is blank', function (): void {
    config([
        'app.env' => 'staging',
        'cashier.webhook.secret' => '',
    ]);

    $this->postJson(route('cashier.webhook'), [])->assertServiceUnavailable();
});
