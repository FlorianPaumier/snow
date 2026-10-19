<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\UserRole;
use App\Models\Client;
use App\Models\TourTemplate;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

final readonly class SaveTourTemplate
{
    /** @param array{name: string, worker_id: string, departure_address?: string|null, arrival_address?: string|null, client_ids: list<string>} $data */
    public function handle(User $admin, array $data, ?TourTemplate $template = null): TourTemplate
    {
        Gate::forUser($admin)->authorize('manage-tours');

        return DB::transaction(function () use ($admin, $data, $template): TourTemplate {
            $record = $template instanceof TourTemplate ? TourTemplate::query()
                ->where('organization_id', $admin->organization_id)->lockForUpdate()->findOrFail($template->id) : new TourTemplate;
            User::query()->where('organization_id', $admin->organization_id)->where('role', UserRole::ROLE_WORKER)->findOrFail($data['worker_id']);
            $clients = Client::query()->where('organization_id', $admin->organization_id)->whereNotAnonymized()
                ->whereIn('id', $data['client_ids'])->lockForUpdate()->get();
            throw_if($data['client_ids'] === [] || $clients->count() !== count($data['client_ids']), ValidationException::withMessages(['client_ids' => __('tours.invalid_clients')]));
            $record->fill([...$data, 'organization_id' => $admin->organization_id])->save();

            return $record;
        });
    }
}
