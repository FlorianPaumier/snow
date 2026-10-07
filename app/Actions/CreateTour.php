<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\UserRole;
use App\Models\Client;
use App\Models\Tour;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

final readonly class CreateTour
{
    /**
     * @param  array{name: string, worker_id: string, scheduled_date: string, departure_address?: string|null, arrival_address?: string|null, client_ids: list<string>}  $data
     */
    public function handle(User $admin, array $data): Tour
    {
        Gate::forUser($admin)->authorize('manage-tours');

        return DB::transaction(function () use ($admin, $data): Tour {
            User::query()->where('organization_id', $admin->organization_id)
                ->where('role', UserRole::ROLE_WORKER)->findOrFail($data['worker_id']);
            $clients = Client::query()->where('organization_id', $admin->organization_id)
                ->whereNotAnonymized()->whereIn('id', $data['client_ids'])->lockForUpdate()->get()->keyBy('id');

            if ($clients->count() !== count($data['client_ids'])) {
                throw ValidationException::withMessages(['client_ids' => __('tours.invalid_clients')]);
            }

            $stops = [];

            foreach ($data['client_ids'] as $id) {
                $client = $clients->get($id) ?? throw ValidationException::withMessages(['client_ids' => __('tours.invalid_clients')]);
                $address = $client->address;
                $stops[] = [
                    'id' => $id,
                    'name' => $client->displayName(),
                    'address' => implode(', ', [$address->street, $address->city, $address->postalCode, $address->country]),
                    'remark' => $client->remark,
                ];
            }

            return Tour::query()->create([
                'organization_id' => $admin->organization_id,
                'worker_id' => $data['worker_id'],
                'name' => $data['name'],
                'scheduled_date' => $data['scheduled_date'],
                'departure_address' => $data['departure_address'] ?? null,
                'arrival_address' => $data['arrival_address'] ?? null,
                'stops' => $stops,
            ]);
        });
    }
}
