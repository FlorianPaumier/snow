<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\AcceptUserInvitation;
use App\Http\Requests\AcceptUserInvitationRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

final readonly class UserInvitationController
{
    public function create(Request $request): Response
    {
        $email = $request->string('email')->value();

        $user = $email !== '' ? User::query()->where('email', $email)->first() : null;

        return Inertia::render('user-invitation/create', [
            'token' => $request->route('token'),
            'email' => $email,
            'organizationName' => $user?->organization?->name,
        ]);
    }

    public function store(AcceptUserInvitationRequest $request, AcceptUserInvitation $action): RedirectResponse
    {
        /** @var array<string, mixed> $credentials */
        $credentials = $request->only('email', 'password', 'password_confirmation', 'token');

        $status = $action->handle($credentials, $request->string('password')->value());

        throw_if(! is_string($status) || $status !== Password::PASSWORD_RESET, ValidationException::withMessages([
            'email' => [__(is_string($status) ? $status : '')],
        ]));

        return to_route('login')->with('status', __('statuses.invitation_accepted'));
    }
}
