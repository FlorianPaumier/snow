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
        $validated = $request->safe()->except('password');

        $organization = $createOrganization->handle([
            'name' => $validated['organization'],
            'userName' => $validated['name'],
            'email' => $validated['email'],
        ], $request->string('password')->value());

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
        Auth::logout();

        $action->handle($user);

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return to_route('home');
    }
}
