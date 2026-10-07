<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\Tour;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

final readonly class FinishTour
{
    public function handle(User $worker, Tour $tour): void
    {
        Gate::forUser($worker)->authorize('operate-tour', $tour);

        DB::transaction(function () use ($tour): void {
            $locked = Tour::query()->whereKey($tour->id)->lockForUpdate()->firstOrFail();

            if ($locked->started_at === null) {
                throw ValidationException::withMessages(['tour' => __('tours.cannot_finish')]);
            }

            if ($locked->finished_at === null) {
                $locked->update(['finished_at' => now()]);
            }
        });
    }
}
