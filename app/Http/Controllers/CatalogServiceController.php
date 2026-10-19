<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\SaveCatalogService;
use App\Http\Requests\SaveCatalogServiceRequest;
use App\Models\CatalogService;
use App\Models\User;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

final readonly class CatalogServiceController
{
    public function index(): Response
    {
        return Inertia::render('catalog-services/index', [
            'services' => CatalogService::query()->orderBy('label')->get(['id', 'label', 'amount']),
        ]);
    }

    public function store(SaveCatalogServiceRequest $request, #[CurrentUser] User $user, SaveCatalogService $save): RedirectResponse
    {
        $save->handle($user, $request->serviceData());

        return to_route('catalog-services.index')->with('status', __('catalog.saved'));
    }

    public function update(SaveCatalogServiceRequest $request, CatalogService $catalogService, #[CurrentUser] User $user, SaveCatalogService $save): RedirectResponse
    {
        $save->handle($user, $request->serviceData(), $catalogService);

        return to_route('catalog-services.index')->with('status', __('catalog.saved'));
    }

    public function destroy(CatalogService $catalogService): RedirectResponse
    {
        $catalogService->delete();

        return to_route('catalog-services.index')->with('status', __('catalog.archived'));
    }
}
