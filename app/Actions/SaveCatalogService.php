<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\CatalogService;
use App\Models\User;
use Illuminate\Support\Facades\Gate;

final readonly class SaveCatalogService
{
    /** @param array{label: string, amount: int} $data */
    public function handle(User $admin, array $data, ?CatalogService $service = null): CatalogService
    {
        Gate::forUser($admin)->authorize('manage-contracts');
        $record = $service instanceof CatalogService
            ? CatalogService::query()->where('organization_id', $admin->organization_id)->findOrFail($service->id)
            : new CatalogService;
        $record->fill([...$data, 'organization_id' => $admin->organization_id])->save();

        return $record;
    }
}
