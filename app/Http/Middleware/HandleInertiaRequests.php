<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Actions\GetSubscriptionSummary;
use Illuminate\Http\Request;
use Inertia\Middleware;

final class HandleInertiaRequests extends Middleware
{
    /**
     * @see https://inertiajs.com/server-side-setup#root-template
     *
     * @var string
     */
    protected $rootView = 'app';

    public function __construct(private readonly GetSubscriptionSummary $getSubscriptionSummary) {}

    /**
     * @see https://inertiajs.com/asset-versioning
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * @see https://inertiajs.com/shared-data
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        return [
            ...parent::share($request),
            'name' => config('app.name'),
            'auth' => [
                'user' => $request->user(),
                'organization' => $request->user()?->organization_id !== null
                    ? ['id' => $request->user()->organization_id, 'name' => $request->user()->organization->name]
                    : null,
                'subscription' => $this->getSubscriptionSummary->handle($request->user()?->organization),
                'can' => [
                    'manageUsers' => $request->user()?->can('manage-users') ?? false,
                    'manageClients' => $request->user()?->can('manage-clients') ?? false,
                    'manageContracts' => $request->user()?->can('manage-contracts') ?? false,
                    'manageBilling' => $request->user()?->can('manage-billing') ?? false,
                ],
            ],
            'sidebarOpen' => ! $request->hasCookie('sidebar_state') || $request->cookie('sidebar_state') === 'true',
        ];
    }
}
