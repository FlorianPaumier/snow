<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\OptimizeTour;
use App\Models\Tour;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

final readonly class OptimizeTourController
{
    public function __invoke(Request $request, Tour $tour, OptimizeTour $optimize): RedirectResponse
    {
        $optimize->handle($request->user() ?? abort(403), $tour);

        return to_route('tours.show', $tour);
    }
}
