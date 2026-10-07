<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\Tour;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

final readonly class SaveTourOrder
{
    /**
     * @param  list<string>  $clientIds
     * @param  list<array{id: string, name: string, address: string, remark: string|null}>|null  $expectedStops
     */
    public function handle(User $admin, Tour $tour, array $clientIds, bool $optimized = false, ?array $expectedStops = null): void
    {
        Gate::forUser($admin)->authorize('manage-tour', $tour);

        DB::transaction(function () use ($tour, $clientIds, $optimized, $expectedStops): void {
            $locked = Tour::query()->whereKey($tour->id)->lockForUpdate()->firstOrFail();

            if ($locked->started_at !== null || ($expectedStops !== null && $locked->stops !== $expectedStops)) {
                throw ValidationException::withMessages(['tour' => __('tours.order_locked')]);
            }

            $stops = collect($locked->stops)->keyBy('id');

            if (count($clientIds) !== $stops->count() || count(array_unique($clientIds)) !== count($clientIds)
                || array_diff($clientIds, $stops->keys()->all()) !== []) {
                throw ValidationException::withMessages(['client_ids' => __('tours.invalid_order')]);
            }

            $locked->update([
                'stops' => array_map(fn (string $id): array => $stops->get($id) ?? throw ValidationException::withMessages(['client_ids' => __('tours.invalid_order')]), $clientIds),
                'optimized_at' => $optimized ? now() : null,
            ]);
        });
    }
}
