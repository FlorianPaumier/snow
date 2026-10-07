<?php

declare(strict_types=1);

namespace App\Providers;

use App\Enums\UserRole;
use App\Models\Organization;
use App\Models\Tour;
use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Laravel\Cashier\Cashier;

final class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // The organization is the Stripe customer, not the user...
        Cashier::useCustomerModel(Organization::class);
    }

    public function boot(): void
    {
        Gate::define('manage-users', fn (User $user): bool => $user->role === UserRole::ROLE_ADMIN);
        Gate::define('manage-tours', fn (User $user): bool => $user->role === UserRole::ROLE_ADMIN);
        Gate::define('use-worker-dashboard', fn (User $user): bool => $user->role === UserRole::ROLE_WORKER);
        Gate::define('manage-tour', fn (User $user, Tour $tour): bool => $user->role === UserRole::ROLE_ADMIN && $user->organization_id === $tour->organization_id);
        Gate::define('operate-tour', fn (User $user, Tour $tour): bool => $user->role === UserRole::ROLE_WORKER && $user->organization_id === $tour->organization_id && $user->id === $tour->worker_id);
        Gate::define('view-tour', fn (User $user, Tour $tour): bool => $user->organization_id === $tour->organization_id && ($user->role === UserRole::ROLE_ADMIN || ($user->role === UserRole::ROLE_WORKER && $user->id === $tour->worker_id)));
        Gate::define('manage-clients', fn (User $user): bool => $user->role === UserRole::ROLE_ADMIN);
        Gate::define('manage-contracts', fn (User $user): bool => $user->role === UserRole::ROLE_ADMIN);
        Gate::define('manage-billing', fn (User $user): bool => $user->role === UserRole::ROLE_ADMIN);
        Gate::define('manage-organization', fn (User $user): bool => $user->role === UserRole::ROLE_ADMIN);

        // Renaming the organization is admin work; moving ownership is the
        // owner's alone...
        Gate::define('transfer-organization', fn (User $user, Organization $organization): bool => $organization->owner_id === $user->id);
    }
}
