<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\CreateUser;
use App\Actions\DeleteUser;
use App\Actions\UpdateUser;
use App\Http\Requests\CreateUserManagementRequest;
use App\Http\Requests\UpdateUserManagementRequest;
use App\Models\User;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

final readonly class UserManagementController
{
    public function index(Request $request): Response
    {
        /** @var array{search?: string, sort?: string, direction?: string}  $validated */
        $validated = $request->validate([
            'search' => ['nullable', 'string', 'max:255'],
            'sort' => ['nullable', 'string', Rule::in($this->sortableColumns())],
            'direction' => ['nullable', 'string', Rule::in(['asc', 'desc'])],
        ]);

        $search = $validated['search'] ?? null;
        $sort = in_array($validated['sort'] ?? null, $this->sortableColumns(), true) ? $validated['sort'] : 'created_at';
        $direction = ($validated['direction'] ?? null) === 'asc' ? 'asc' : 'desc';

        /** @var LengthAwarePaginator<int, User>  $users */
        $users = User::query()
            ->when($search !== null, fn (Builder $query): Builder => $query->where(
                fn (Builder $query): Builder => $query
                    ->where('name', 'like', sprintf('%%%s%%', $search))
                    ->orWhere('email', 'like', sprintf('%%%s%%', $search))
            ))
            ->orderBy($sort, $direction)
            ->paginate(perPage: 15)
            ->withQueryString();

        return Inertia::render('user-management/index', [
            'users' => $users->through(fn (User $user): array => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role,
                'status' => $user->hasPendingInvitation() ? 'pending' : 'active',
                'is_owner' => $user->id === $request->user()->organization->owner_id,
                'created_at' => $user->created_at->toDateString(),
            ]),
            'filters' => [
                'search' => $search ?? '',
                'sort' => $sort,
                'direction' => $direction,
            ],
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('user-management/create');
    }

    public function store(CreateUserManagementRequest $request, CreateUser $action): RedirectResponse
    {
        $action->handle($request->validated());

        return to_route('users.index')->with('status', __('statuses.user_created'));
    }

    public function edit(User $user): Response
    {
        return Inertia::render('user-management/edit', [
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role,
            ],
        ]);
    }

    public function update(UpdateUserManagementRequest $request, User $user, UpdateUser $action): RedirectResponse
    {
        $action->handle($user, $request->validated());

        return to_route('users.index')->with('status', __('statuses.user_updated'));
    }

    public function destroy(User $user, DeleteUser $action): RedirectResponse
    {
        $action->handle($user);

        return to_route('users.index')->with('status', __('statuses.user_deleted'));
    }

    /**
     * @return list<string>
     */
    private function sortableColumns(): array
    {
        return ['name', 'email', 'role', 'created_at'];
    }
}
