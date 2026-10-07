<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Tour;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

final readonly class WorkerDashboardController
{
    public function index(Request $request): Response
    {
        $worker = $request->user() ?? abort(403);

        return Inertia::render('tours/worker', [
            'tours' => Tour::query()->where('worker_id', $worker->id)->whereNull('finished_at')
                ->orderByRaw('CASE WHEN started_at IS NULL THEN 1 ELSE 0 END')->oldest('scheduled_date')->get(),
            'history' => Tour::query()->where('worker_id', $worker->id)->whereNotNull('finished_at')
                ->latest('finished_at')->paginate(15),
            'today' => today()->toDateString(),
        ]);
    }
}
