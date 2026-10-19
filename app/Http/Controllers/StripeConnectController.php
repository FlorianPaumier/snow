<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\OnboardStripeAccount;
use App\Models\User;
use App\Support\StripeConnect;
use Illuminate\Container\Attributes\CurrentUser;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\Response as HttpResponse;

final readonly class StripeConnectController
{
    public function show(#[CurrentUser] User $user): Response
    {
        $configured = config()->string('services.stripe_connect.key') !== '' && config()->string('services.stripe_connect.webhook_secret') !== '';
        $id = $user->organization->stripe_connect_id;
        $ready = false;
        if ($configured && $id !== null) {
            $stripe = resolve(StripeConnect::class);
            $ready = $stripe->ready($stripe->account($id));
        }

        return Inertia::render('payments/settings', ['configured' => $configured, 'account' => $id, 'ready' => $ready]);
    }

    public function store(#[CurrentUser] User $user): HttpResponse
    {
        abort_if(config()->string('services.stripe_connect.key') === '' || config()->string('services.stripe_connect.webhook_secret') === '', 503);

        return Inertia::location(resolve(OnboardStripeAccount::class)->handle($user));
    }
}
