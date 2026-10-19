<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\CreateTour;
use App\Actions\SaveTourOrder;
use App\Enums\UserRole;
use App\Http\Requests\StoreTourRequest;
use App\Http\Requests\UpdateTourOrderRequest;
use App\Models\Client;
use App\Models\Tour;
use App\Models\TourNotification;
use App\Models\User;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

final readonly class TourController
{
    public function index(Request $request): Response
    {
        /** @var array{date?: string|null} $filters */
        $filters = $request->validate(['date' => ['nullable', 'date_format:Y-m-d']]);
        $date = $filters['date'] ?? null;
        $query = Tour::query()->when($date !== null, fn (Builder $query): Builder => $query->whereDate('scheduled_date', $date));

        return Inertia::render('tours/index', [
            'tours' => (clone $query)->with('worker:id,name,organization_id')->latest('scheduled_date')->orderByDesc('id')->paginate(20)->withQueryString(),
            'date' => $date ?? '',
            'counts' => [
                'assigned' => (clone $query)->whereNotNull('worker_id')->count(),
                'planned' => (clone $query)->whereNull('started_at')->whereNull('finished_at')->count(),
                'active' => (clone $query)->whereNotNull('started_at')->whereNull('finished_at')->count(),
                'finished' => (clone $query)->whereNotNull('finished_at')->count(),
            ],
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('tours/create', [
            'workers' => User::query()->where('role', UserRole::ROLE_WORKER)->orderBy('name')->get(['id', 'name']),
            'clients' => Client::query()->whereNotAnonymized()->orderBy('lastName')->get()->map(fn (Client $client): array => [
                'id' => $client->id,
                'name' => $client->displayName(),
                'address' => implode(', ', [$client->address->street, $client->address->city, $client->address->postalCode, $client->address->country]),
            ]),
            'today' => today()->toDateString(),
        ]);
    }

    public function store(StoreTourRequest $request, CreateTour $create): RedirectResponse
    {
        /** @var array{name: string, worker_id: string, scheduled_date: string, departure_address?: string|null, arrival_address?: string|null, client_ids: list<string>} $data */
        $data = $request->validated();
        $tour = $create->handle($request->user() ?? abort(403), $data);

        return to_route('tours.show', $tour);
    }

    public function show(Tour $tour): Response
    {
        Gate::authorize('view-tour', $tour);

        return Inertia::render('tours/show', [
            'tour' => $tour->load('worker:id,name,organization_id'),
            'announcements' => Gate::allows('manage-tour', $tour) ? TourNotification::query()->where('organization_id', $tour->organization_id)->where('tour_id', $tour->id)->get(['event', 'status', 'reason'])->map(fn (TourNotification $notice): array => $notice->only(['event', 'status', 'reason']))->all() : [],
            'optimizationAvailable' => mb_trim(config()->string('services.openrouteservice.key')) !== '',
        ]);
    }

    public function update(UpdateTourOrderRequest $request, Tour $tour, SaveTourOrder $save): RedirectResponse
    {
        /** @var list<string> $clientIds */
        $clientIds = $request->validated('client_ids');
        $save->handle($request->user() ?? abort(403), $tour, $clientIds);

        return to_route('tours.show', $tour);
    }
}
