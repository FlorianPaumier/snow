<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\SaveTourTemplate;
use App\Enums\UserRole;
use App\Http\Requests\StoreTourTemplateRequest;
use App\Models\Client;
use App\Models\TourTemplate;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

final readonly class TourTemplateController
{
    public function index(): Response
    {
        $templates = TourTemplate::query()->with('worker')->orderBy('id')->paginate(20);
        $ids = $templates->getCollection()->flatMap(fn (TourTemplate $template): array => $template->client_ids);
        $clients = Client::query()->whereNotAnonymized()->whereIn('id', $ids)->get()->keyBy('id');

        return Inertia::render('tour-templates/index', [
            'templates' => $templates->through(function (TourTemplate $template) use ($clients): array {
                $stops = [];
                foreach ($template->client_ids as $id) {
                    $client = $clients->get($id);
                    if ($client === null) {
                        continue;
                    }

                    $address = $client->address;
                    $stops[] = ['id' => $id, 'name' => $client->displayName(), 'remark' => $client->remark,
                        'address' => implode(', ', [$address->street, $address->city, $address->postalCode, $address->country])];
                }

                $workerValid = $template->worker?->role === UserRole::ROLE_WORKER;

                return ['id' => $template->id, 'name' => $template->name, 'worker' => $workerValid ? $template->worker->name : null,
                    'valid' => $workerValid && count($stops) === count($template->client_ids) && $stops !== [],
                    'missingClients' => count($template->client_ids) - count($stops), 'stops' => $stops,
                    'departure_address' => $template->departure_address, 'arrival_address' => $template->arrival_address];
            }),
            'requestKey' => (string) Str::uuid(),
            'today' => today()->toDateString(),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('tours/create', [...$this->options(), 'templateMode' => true]);
    }

    public function store(StoreTourTemplateRequest $request, SaveTourTemplate $save): RedirectResponse
    {
        $save->handle($request->user() ?? abort(403), $request->templateData());

        return to_route('tour-templates.index');
    }

    public function edit(TourTemplate $tourTemplate): Response
    {
        return Inertia::render('tours/create', [...$this->options(), 'templateMode' => true,
            'template' => $tourTemplate->only(['id', 'name', 'worker_id', 'client_ids', 'departure_address', 'arrival_address'])]);
    }

    public function update(StoreTourTemplateRequest $request, TourTemplate $tourTemplate, SaveTourTemplate $save): RedirectResponse
    {
        $save->handle($request->user() ?? abort(403), $request->templateData(), $tourTemplate);

        return to_route('tour-templates.index');
    }

    public function destroy(TourTemplate $tourTemplate): RedirectResponse
    {
        $tourTemplate->delete();

        return to_route('tour-templates.index');
    }

    /** @return array<string, mixed> */
    private function options(): array
    {
        return [
            'workers' => User::query()->where('role', UserRole::ROLE_WORKER)->orderBy('name')->get(['id', 'name']),
            'clients' => Client::query()->whereNotAnonymized()->orderBy('lastName')->get()->map(fn (Client $client): array => [
                'id' => $client->id, 'name' => $client->displayName(),
                'address' => implode(', ', [$client->address->street, $client->address->city, $client->address->postalCode, $client->address->country]),
            ]),
            'today' => today()->toDateString(),
        ];
    }
}
