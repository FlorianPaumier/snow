<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\GetClientEvolution;
use App\Actions\GetOnboardingChecklist;
use App\Actions\GetRevenueEvolution;
use App\Actions\GetSeasonRevenueRecap;
use App\Actions\GetSeasonSummary;
use App\Actions\GetUnpaidInstallments;
use App\Enums\PaymentSlot;
use App\Enums\UserRole;
use App\Models\Contract;
use App\Support\TenantCache;
use App\ValueObjects\Season;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

final readonly class DashboardController
{
    public function __construct(private TenantCache $cache) {}

    public function index(
        Request $request,
        GetSeasonSummary $summary,
        GetSeasonRevenueRecap $recap,
        GetUnpaidInstallments $unpaidInstallments,
        GetRevenueEvolution $revenueEvolution,
        GetClientEvolution $clientEvolution,
        GetOnboardingChecklist $onboardingChecklist,
    ): Response|RedirectResponse {
        if ($request->user()?->role === UserRole::ROLE_WORKER) {
            return to_route('tours.worker');
        }

        /** @var list<string> $seasons */
        $seasons = $this->cache->remember(
            'seasons-array',
            [],
            fn (): array => Contract::query()
                ->distinct()
                ->orderByDesc('season')
                ->pluck('season')->all(),
            TenantCache::TTL_SHORT,
        );

        /** @var array{season?: string, slot?: string}  $validated */
        $validated = $request->validate([
            'season' => ['nullable', 'string', Rule::in($seasons)],
            'slot' => ['nullable', 'string', Rule::enum(PaymentSlot::class)],
        ]);

        $season = isset($validated['season'])
            ? Season::fromString($validated['season'])
            : Season::current();

        $slot = isset($validated['slot'])
            ? PaymentSlot::from($validated['slot'])
            : PaymentSlot::SLOT_SECOND_INSTALLMENT;

        // The checklist is only shared while steps remain, so it naturally
        // disappears from the payload (and the page) once the organization
        // has performed each activation action...
        $checklist = $onboardingChecklist->handle();
        $hasIncompleteSteps = $checklist !== null && $checklist['incompleteCount'] > 0;

        return Inertia::render('dashboard', [
            'season' => $season->label,
            'seasons' => $seasons,
            'summary' => $summary->handle($season),
            'unpaidInstallments' => $unpaidInstallments->handle($season, $slot),
            'seasonRecap' => $recap->handle(),
            'revenueEvolution' => $revenueEvolution->handle(),
            'clientEvolution' => $clientEvolution->handle(),
            'slot' => $slot->value,
            ...($hasIncompleteSteps ? ['onboardingChecklist' => $checklist] : []),
        ]);
    }
}
