<?php

declare(strict_types=1);

namespace App\Actions;

use App\Mail\UserInvitationMail;
use App\Models\User;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Password;

final readonly class SendUserInvitation
{
    /**
     * Email a set-password invitation. The token lives in the dedicated
     * invitations broker (72 hours) and never gives anyone but the
     * invited person a credential.
     */
    public function handle(User $user, ?string $inviterName = null): void
    {
        $token = Password::broker('invitations')->createToken($user);

        Mail::to($user)->send(
            new UserInvitationMail($user->email, $user->organization->name, $token, $inviterName)->afterCommit(),
        );
    }
}
