<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\DispatchTourTemplates;
use App\Http\Requests\DispatchTourTemplatesRequest;
use Illuminate\Http\RedirectResponse;

final readonly class TourDispatchController
{
    public function __invoke(DispatchTourTemplatesRequest $request, DispatchTourTemplates $dispatch): RedirectResponse
    {
        $batch = $dispatch->handle($request->user() ?? abort(403), $request->dispatchData());

        return to_route('tours.index', ['date' => $batch->scheduled_date->toDateString()])->with('status', __('tour_templates.dispatched'));
    }
}
