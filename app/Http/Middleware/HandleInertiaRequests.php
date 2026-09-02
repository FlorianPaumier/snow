<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Actions\GetSubscriptionAccess;
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

    public function __construct(
        private readonly GetSubscriptionSummary $getSubscriptionSummary,
        private readonly GetSubscriptionAccess $getSubscriptionAccess,
    ) {}

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
        $user = $request->user();

        $organization = null;

        if ($user?->organization_id !== null) {
            $organization = [
                'id' => $user->organization_id,
                'name' => $user->organization->name,
            ];
        }

        return [
            ...parent::share($request),
            'name' => config('app.name'),
            'locale' => app()->getLocale(),
            'auth' => [
                'user' => $user,
                'organization' => $organization,
                'subscription' => $this->getSubscriptionSummary->handle($user?->organization),
                'access' => $this->getSubscriptionAccess->handle($user?->organization),
                'can' => [
                    'manageUsers' => $user?->can('manage-users') ?? false,
                    'manageClients' => $user?->can('manage-clients') ?? false,
                    'manageContracts' => $user?->can('manage-contracts') ?? false,
                    'manageBilling' => $user?->can('manage-billing') ?? false,
                ],
            ],
            'sidebarOpen' => ! $request->hasCookie('sidebar_state') || $request->cookie('sidebar_state') === 'true',
        ];
    }
}
