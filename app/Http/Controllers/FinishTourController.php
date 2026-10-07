<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\FinishTour;
use App\Models\Tour;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

final readonly class FinishTourController
{
    public function __invoke(Request $request, Tour $tour, FinishTour $finish): RedirectResponse
    {
        $finish->handle($request->user() ?? abort(403), $tour);

        return to_route('tours.worker');
    }
}
