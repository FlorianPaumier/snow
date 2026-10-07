<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\Tour;
use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use RuntimeException;

final readonly class OptimizeTour
{
    public function __construct(
        private GeocodeTourAddress $geocode,
        private OptimizeTourOrder $optimize,
        private SaveTourOrder $save,
    ) {}

    public function handle(User $admin, Tour $tour): void
    {
        Gate::forUser($admin)->authorize('manage-tour', $tour);

        if ($tour->started_at !== null) {
            throw ValidationException::withMessages(['tour' => __('tours.order_locked')]);
        }

        if (count($tour->stops) > config()->integer('services.openrouteservice.max_stops')) {
            throw ValidationException::withMessages(['optimization' => __('tours.too_many_stops', ['count' => config()->integer('services.openrouteservice.max_stops')])]);
        }

        if ($tour->departure_address === null || mb_trim($tour->departure_address) === '') {
            throw ValidationException::withMessages(['optimization' => __('tours.departure_required')]);
        }

        $start = $this->geocode->handle($tour->departure_address, $tour->organization_id);
        $end = $this->geocode->handle($tour->arrival_address ?: $tour->departure_address, $tour->organization_id);
        $stops = [];

        foreach ($tour->stops as $stop) {
            $stops[] = ['id' => $stop['id'], ...$this->geocode->handle($stop['address'], $tour->organization_id)];
        }

        try {
            $order = $this->optimize->handle($stops, $start, $end);
        } catch (RuntimeException) {
            throw ValidationException::withMessages(['optimization' => __('tours.optimization_invalid_response')]);
        }

        $this->save->handle($admin, $tour, $order, optimized: true, expectedStops: $tour->stops);
    }
}
