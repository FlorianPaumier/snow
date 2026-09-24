<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\CreateOrganization;
use App\Actions\CreateUserLoginChallengeCode;
use App\Actions\DeleteUser;
use App\Http\Requests\CreateUserRequest;
use App\Http\Requests\DeleteUserRequest;
use App\Models\User;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

final readonly class UserController
{
    public function create(): Response
    {
        return Inertia::render('user/create');
    }

    public function store(CreateUserRequest $request, CreateOrganization $createOrganization, CreateUserLoginChallengeCode $challengeCode): RedirectResponse
    {
        /** @var array{organization: string, name: string, email: string} $validated */
        $validated = $request->safe()->except(['password', 'invite_code']);

        $organization = $createOrganization->handle(
            [
                'name' => $validated['organization'],
                'userName' => $validated['name'],
                'email' => $validated['email'],
                'termsAcceptedAt' => now(),
                'termsVersion' => config()->string('legal.terms_version'),
                'privacyVersion' => config()->string('legal.privacy_version'),
                'legalLocale' => app()->getLocale(),
            ],
            $request->string('password')->value(),
            $request->string('invite_code')->value(),
        );

        // The owner is attached to the organization by the action, avoiding
        // the auth-bound global scope on the users table...
        /** @var User $user */
        $user = $organization->users->firstOrFail();

        $challengeCode->handle($user);

        $request->session()->put('login.id', $user->getKey());

        return redirect()->intended(route('login-challenge.show', absolute: false));
    }

    public function destroy(DeleteUserRequest $request, #[CurrentUser] User $user, DeleteUser $action): RedirectResponse
    {
        // The deletion runs first: an owner-protected account must throw
        // before the session is torn down, or the back-redirect bounces off
        // the auth middleware and the error never reaches the user...
        $action->handle($user);

        // The row is gone, so the remember token dies with it; cycling it
        // would write to — and resurrect — the deleted model...
        Auth::logoutCurrentDevice();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return to_route('home');
    }
}
