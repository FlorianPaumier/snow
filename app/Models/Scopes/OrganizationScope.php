<?php

declare(strict_types=1);

namespace App\Models\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;
use Illuminate\Support\Facades\Auth;

/**
 * Restricts every query for the applied model to the current user's
 * organization. This is the backbone of the multi-tenant isolation: any
 * query, including route model bindings, silently ignores other
 * organizations' rows.
 *
 * Unauthenticated contexts (login, password reset, email verification,
 * console commands, queue jobs) are not scoped: authentication middleware
 * already keeps guests away from tenant data, and pre-auth flows must be
 * able to look users up globally.
 */
final class OrganizationScope implements Scope
{
    /**
     * @param  Builder<Model>  $builder
     */
    public function apply(Builder $builder, Model $model): void
    {
        // The session guard resolves the user through a User query, which
        // applies this scope. Asking for the user here mid-retrieval would
        // recurse forever, so only constrain the query once the user is
        // already resolved; the retrieval itself is keyed by the
        // server-side session id and stays unscoped.
        if (! Auth::hasUser()) {
            return;
        }

        $organizationId = Auth::user()?->organization_id;

        if (is_string($organizationId)) {
            $builder->where($model->qualifyColumn('organization_id'), $organizationId);
        }
    }
}
