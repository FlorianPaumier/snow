<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\CreateUserLoginChallengeCode;
use App\Models\User;
use Illuminate\Auth\Events\Lockout;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

final readonly class LoginChallengeController
{
    public function create(Request $request): Response|RedirectResponse
    {
        $user = $this->challengedUser($request);

        if (! $user instanceof User) {
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

        if (! $user instanceof User) {
            return to_route('login');
        }

        $this->ensureIsNotRateLimited($request, $user);

        /** @var array{code: string} $validated */
        $validated = $request->validate([
            'code' => ['required', 'digits:6'],
        ]);

        $isValid = $user->login_challenge_code !== null
            && $user->login_challenge_code_expires_at !== null
            && $user->login_challenge_code_expires_at->isFuture()
            && Hash::check($validated['code'], $user->login_challenge_code);

        if (! $isValid) {
            RateLimiter::hit($this->throttleKey($user));

            return back()->withErrors(['code' => __('contracts.invalid_code')]);
        }

        RateLimiter::clear($this->throttleKey($user));

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

        if (! $user instanceof User) {
            return to_route('login');
        }

        $action->handle($user);

        return back()->with('status', 'login-challenge-code-sent');
    }

    /**
     * @throws ValidationException
     */
    private function ensureIsNotRateLimited(Request $request, User $user): void
    {
        $key = $this->throttleKey($user);

        if (! RateLimiter::tooManyAttempts($key, 5)) {
            return;
        }

        event(new Lockout($request));

        $seconds = RateLimiter::availableIn($key);

        throw ValidationException::withMessages([
            'code' => __('auth.throttle', [
                'seconds' => $seconds,
                'minutes' => ceil($seconds / 60),
            ]),
        ]);
    }

    /**
     * Get the rate-limiting throttle key for the challenge attempt.
     *
     * Keyed by the challenged login and email so the counter follows the
     * account even when the session id rotates between attempts.
     */
    private function throttleKey(User $user): string
    {
        return 'login-challenge|'.$user->getKey().'|'.mb_strtolower($user->email);
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

        // The session value is a scalar uuid; the is_string check also
        // narrows find()'s array-id overload away for the type checker.
        if (is_string($loginId) === false || $loginId === '') {
            return null;
        }

        return User::query()->withoutGlobalScopes()->find($loginId);
    }
}
