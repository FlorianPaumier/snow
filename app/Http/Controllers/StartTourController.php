<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\StartTour;
use App\Models\Tour;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

final readonly class StartTourController
{
    public function __invoke(Request $request, Tour $tour, StartTour $start): RedirectResponse
    {
        $start->handle($request->user() ?? abort(403), $tour);

        return to_route('tours.worker');
    }
}
