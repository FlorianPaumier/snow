<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\User;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use SensitiveParameter;

final readonly class AcceptUserInvitation
{
    /**
     * @param  array<string, mixed>  $credentials
     */
    public function handle(array $credentials, #[SensitiveParameter] string $password): string
    {
        return DB::transaction(fn (): string => Password::broker('invitations')->reset(
            $credentials,
            function (User $user) use ($password): void {
                $user->update([
                    'password' => $password,
                    'remember_token' => Str::random(60),
                    'invitation_accepted_at' => now(),
                    // The invitation link proves control of the inbox, so
                    // accepting doubles as email verification...
                    'email_verified_at' => $user->email_verified_at ?? now(),
                ]);

                event(new PasswordReset($user));
            },
        ));
    }
}
