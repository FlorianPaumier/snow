<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\GetClientEvolution;
use App\Actions\GetRevenueEvolution;
use App\Actions\GetSeasonRevenueRecap;
use App\Actions\GetSeasonSummary;
use App\Actions\GetUnpaidInstallments;
use App\Enums\PaymentSlot;
use App\Models\Contract;
use App\ValueObjects\Season;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

final readonly class DashboardController
{
    public function index(
        Request $request,
        GetSeasonSummary $summary,
        GetSeasonRevenueRecap $recap,
        GetUnpaidInstallments $unpaidInstallments,
        GetRevenueEvolution $revenueEvolution,
        GetClientEvolution $clientEvolution,
    ): Response {
        /** @var Collection<int, string> $seasons */
        $seasons = Contract::query()
            ->distinct()
            ->orderByDesc('season')
            ->pluck('season');

        /** @var array{season?: string, slot?: string}  $validated */
        $validated = $request->validate([
            'season' => ['nullable', 'string', Rule::in($seasons->all())],
            'slot' => ['nullable', 'string', Rule::enum(PaymentSlot::class)],
        ]);

        $season = isset($validated['season'])
            ? Season::fromString($validated['season'])
            : Season::current();

        $slot = isset($validated['slot'])
            ? PaymentSlot::from($validated['slot'])
            : PaymentSlot::SLOT_SECOND_INSTALLMENT;

        return Inertia::render('dashboard', [
            'season' => $season->label,
            'seasons' => $seasons->all(),
            'summary' => $summary->handle($season),
            'unpaidInstallments' => $unpaidInstallments->handle($season, $slot),
            'seasonRecap' => $recap->handle(),
            'revenueEvolution' => $revenueEvolution->handle(),
            'clientEvolution' => $clientEvolution->handle(),
            'slot' => $slot->value,
        ]);
    }
}
