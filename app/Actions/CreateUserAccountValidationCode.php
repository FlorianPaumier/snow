<?php

declare(strict_types=1);

namespace App\Actions;

use App\Mail\AccountValidationCodeMail;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;

final class CreateUserAccountValidationCode
{
    public function handle(User $user): string
    {
        $code = (string) random_int(100000, 999999);

        $user->forceFill([
            'validation_code' => Hash::make($code),
            'validation_code_expires_at' => now()->addMinutes(15),
        ])->save();

        Mail::to($user->email)->send(new AccountValidationCodeMail($code));

        return $code;
    }
}
