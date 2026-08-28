<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\CreateUserAccountValidationCode;
use App\Models\User;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Inertia\Inertia;
use Inertia\Response;

final readonly class UserAccountValidationController
{
    public function create(Request $request, #[CurrentUser] User $user): Response|RedirectResponse
    {
        return $user->hasVerifiedEmail()
            ? redirect()->intended(route('dashboard', absolute: false))
            : Inertia::render('user-account-validation/create', [
                'email' => $user->email,
                'status' => $request->session()->get('status'),
            ]);
    }

    public function store(Request $request, #[CurrentUser] User $user): RedirectResponse
    {
        if ($user->hasVerifiedEmail()) {
            return redirect()->intended(route('dashboard', absolute: false));
        }

        /** @var array{code: string} $validated */
        $validated = $request->validate([
            'code' => ['required', 'digits:6'],
        ]);

        $isValid = $user->validation_code !== null
            && $user->validation_code_expires_at !== null
            && $user->validation_code_expires_at->isFuture()
            && Hash::check($validated['code'], $user->validation_code);

        if (! $isValid) {
            return back()->withErrors(['code' => 'The provided code is invalid or has expired.']);
        }

        $user->markEmailAsVerified();
        $user->forceFill([
            'validation_code' => null,
            'validation_code_expires_at' => null,
        ])->save();

        return redirect()->intended(route('dashboard', absolute: false).'?validated=1');
    }

    public function update(#[CurrentUser] User $user, CreateUserAccountValidationCode $action): RedirectResponse
    {
        if ($user->hasVerifiedEmail()) {
            return redirect()->intended(route('dashboard', absolute: false));
        }

        $action->handle($user);

        return back()->with('status', 'validation-code-sent');
    }
}
