<?php

declare(strict_types=1);

namespace App\Models\Concerns;

use App\Models\Contract;
use App\Models\Organization;
use App\Models\Scopes\OrganizationScope;
use Illuminate\Database\Eloquent\Attributes\ScopedBy;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Auth;

/**
 * Makes the using model organization-scoped: every query is restricted to
 * the current user's organization and the organization is stamped
 * automatically on create.
 *
 * @property-read string $organization_id
 *
 * @mixin Model
 */
#[ScopedBy(OrganizationScope::class)]
trait BelongsToOrganization
{
    /**
     * Boot the trait. Stamp the current organization on new records as a
     * safety net — factories, seeders and actions all inherit it from
     * context, and explicit organization_id assignments still win.
     */
    public static function bootBelongsToOrganization(): void
    {
        static::creating(function (Model $model): void {
            if ($model->organization_id === null) {
                $organizationId = self::resolveOrganizationId($model);

                if (is_string($organizationId)) {
                    $model->organization_id = $organizationId;
                }
            }
        });
    }

    /**
     * @param  Builder<static>  $query
     * @return Builder<static>&static
     */
    public static function query(): Builder
    {
        /** @var Builder<static>&static $query */
        return parent::query();
    }

    /**
     * @return BelongsTo<Organization, $this>
     */
    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    /**
     * Resolve the organization for a new record: the authenticated user's
     * organization, or — when the record was created through a parent
     * relation (e.g. a payment via $contract->payments()) — the parent
     * identified by the freshly set contract_id.
     */
    private static function resolveOrganizationId(Model $model): ?string
    {
        $organizationId = Auth::user()?->organization_id;

        if (is_string($organizationId)) {
            return $organizationId;
        }

        $contractId = $model->getAttribute('contract_id');

        if (is_string($contractId)) {
            $contract = Contract::withoutGlobalScopes()->find($contractId);

            if ($contract !== null && is_string($contract->organization_id)) {
                return $contract->organization_id;
            }
        }

        return null;
    }
}
