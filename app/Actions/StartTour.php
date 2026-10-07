<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\Tour;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

final readonly class StartTour
{
    public function handle(User $worker, Tour $tour): void
    {
        Gate::forUser($worker)->authorize('operate-tour', $tour);

        DB::transaction(function () use ($worker, $tour): void {
            User::query()->whereKey($worker->id)->lockForUpdate()->firstOrFail();
            $locked = Tour::query()->whereKey($tour->id)->lockForUpdate()->firstOrFail();

            if ($locked->finished_at !== null || $locked->scheduled_date->isAfter(today())) {
                throw ValidationException::withMessages(['tour' => __('tours.cannot_start')]);
            }

            if ($locked->started_at !== null) {
                return;
            }

            if (Tour::query()->where('worker_id', $worker->id)->whereNotNull('started_at')->whereNull('finished_at')->exists()) {
                throw ValidationException::withMessages(['tour' => __('tours.already_active')]);
            }

            $locked->update(['started_at' => now()]);
        });
    }
}
