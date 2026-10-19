<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\PrepareSeasonRenewals;
use App\Http\Requests\PrepareSeasonRenewalsRequest;
use App\Models\Contract;
use App\Models\Quote;
use App\ValueObjects\Season;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Collection;
use Inertia\Inertia;
use Inertia\Response;

final readonly class SeasonRenewalController
{
    public function index(PrepareSeasonRenewalsRequest $request): Response
    {
        $source = $request->sourceSeason();
        $target = Season::fromString($source)->next()->label;
        $contracts = Contract::query()->where('season', $source)->with(['client', 'services'])
            ->orderBy('id')->paginate(25)->withQueryString();
        $clientIds = $contracts->pluck('client_id');
        $existingContracts = Contract::query()->where('season', $target)->whereIn('client_id', $clientIds)->pluck('client_id');
        $existingQuotes = Quote::query()->where('season', $target)->whereIn('client_id', $clientIds)
            ->where('status', '!=', 'replaced')->latest()->get()->keyBy('client_id');

        return Inertia::render('contract/renewals', [
            'sourceSeason' => $source,
            'targetSeason' => $target,
            'seasons' => [Season::current()->previous()->label, Season::current()->label],
            'contracts' => $contracts->through(fn (Contract $contract): array => [
                'id' => $contract->id,
                'name' => $contract->client->displayName(),
                'total' => $contract->services->sum('amount'),
                'anonymized' => $contract->client->isAnonymized(),
                'blocked' => $existingContracts->contains($contract->client_id) || $existingQuotes->has($contract->client_id),
                'quoteId' => $existingQuotes->get($contract->client_id)?->id,
                'status' => $existingQuotes->get($contract->client_id)?->status,
            ]),
            'counts' => $this->counts($source, $target),
            'result' => $request->session()->get('renewalResult'),
        ]);
    }

    public function store(PrepareSeasonRenewalsRequest $request, PrepareSeasonRenewals $prepare): RedirectResponse
    {
        $source = $request->sourceSeason();
        $result = $prepare->handle($request->user() ?? abort(403), $source, Season::fromString($source)->next()->label, $request->contractIds());

        return to_route('renewals.index', ['sourceSeason' => $source])->with('renewalResult', $result);
    }

    /** @return array{draft: int, sent: int, accepted: int, declined: int, expired: int} */
    private function counts(string $source, string $target): array
    {
        $query = Quote::query()->where('season', $target)->where('status', '!=', 'replaced')
            ->whereIn('source_contract_id', Contract::query()->where('season', $source)->select('id'));
        /** @var Collection<string, int|string> $grouped */
        $grouped = (clone $query)->select('status')->selectRaw('COUNT(*) as total')->groupBy('status')->pluck('total', 'status');
        $expired = (clone $query)->where('status', 'sent')->where('expires_at', '<=', now())->count();

        return ['draft' => (int) $grouped->get('draft', 0), 'sent' => (int) $grouped->get('sent', 0) - $expired,
            'accepted' => (int) $grouped->get('accepted', 0), 'declined' => (int) $grouped->get('declined', 0), 'expired' => $expired];
    }
}
