<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\CreateUserEmailResetNotification;
use App\Actions\SendUserInvitation;
use App\Models\User;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;

final readonly class SendUserPasswordResetController
{
    /**
     * Pending members get their invitation again; active members get a
     * short-lived password reset link. The admin never touches a
     * credential in either case.
     */
    public function __invoke(
        Request $request,
        #[CurrentUser] User $actor,
        User $user,
        SendUserInvitation $sendInvitation,
        CreateUserEmailResetNotification $sendResetLink,
    ): RedirectResponse {
        if ($user->hasPendingInvitation()) {
            $sendInvitation->handle($user, $actor->name);

            Inertia::flash('toast', [
                'type' => 'success',
                'message' => __('statuses.invitation_sent'),
            ]);

            return back();
        }

        $sendResetLink->handle(['email' => $user->email]);

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('statuses.reset_link_sent'),
        ]);

        return back();
    }
}
