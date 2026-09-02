<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\GrantAccessUnlock;
use App\Models\User;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;

final readonly class UnlockBillingAccessController
{
    public function __invoke(#[CurrentUser] User $user, GrantAccessUnlock $action): RedirectResponse
    {
        $action->handle($user);

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('statuses.unlock_granted'),
        ]);

        return to_route('billing.index');
    }
}
