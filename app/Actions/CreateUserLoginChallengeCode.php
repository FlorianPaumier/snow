<?php

declare(strict_types=1);

namespace App\Actions;

use App\Mail\LoginChallengeCodeMail;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;

final class CreateUserLoginChallengeCode
{
    public function handle(User $user): string
    {
        $code = (string) random_int(100000, 999999);

        $user->forceFill([
            'login_challenge_code' => Hash::make($code),
            'login_challenge_code_expires_at' => now()->addMinutes(15),
        ])->save();

        Mail::to($user->email)->send(new LoginChallengeCodeMail($code));

        return $code;
    }
}
