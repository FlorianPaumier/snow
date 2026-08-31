<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\CreateUserLoginChallengeCode;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Inertia\Inertia;
use Inertia\Response;

final readonly class LoginChallengeController
{
    public function create(Request $request): Response|RedirectResponse
    {
        $user = $this->challengedUser($request);

        if ($user === null) {
            return to_route('login');
        }

        return Inertia::render('login-challenge/create', [
            'email' => $user->email,
            'status' => $request->session()->get('status'),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $user = $this->challengedUser($request);

        if ($user === null) {
            return to_route('login');
        }

        /** @var array{code: string} $validated */
        $validated = $request->validate([
            'code' => ['required', 'digits:6'],
        ]);

        $isValid = $user->login_challenge_code !== null
            && $user->login_challenge_code_expires_at !== null
            && $user->login_challenge_code_expires_at->isFuture()
            && Hash::check($validated['code'], $user->login_challenge_code);

        if (! $isValid) {
            return back()->withErrors(['code' => 'The provided code is invalid or has expired.']);
        }

        $user->forceFill([
            'login_challenge_code' => null,
            'login_challenge_code_expires_at' => null,
        ])->save();

        if (! $user->hasVerifiedEmail()) {
            $user->markEmailAsVerified();
        }

        Auth::login($user, $request->boolean('remember') || (bool) $request->session()->pull('login.remember', false));

        $request->session()->forget('login.id');
        $request->session()->regenerate();

        return redirect()->intended(route('dashboard', absolute: false));
    }

    public function update(Request $request, CreateUserLoginChallengeCode $action): RedirectResponse
    {
        $user = $this->challengedUser($request);

        if ($user === null) {
            return to_route('login');
        }

        $action->handle($user);

        return back()->with('status', 'login-challenge-code-sent');
    }

    /**
     * Get the user attempting the login challenge from the session.
     *
     * The challenge happens before authentication, so the lookup bypasses
     * the organization scope.
     */
    private function challengedUser(Request $request): ?User
    {
        $loginId = $request->session()->get('login.id');

        if ($loginId === null) {
            return null;
        }

        return User::withoutGlobalScopes()->find($loginId);
    }
}
