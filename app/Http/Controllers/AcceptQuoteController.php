<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\AcceptQuote;
use App\Http\Requests\AcceptQuoteRequest;
use App\Models\Client;
use App\Models\Quote;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

final readonly class AcceptQuoteController
{
    public function show(Request $request, string $organization, string $quote): Response
    {
        $offer = $this->resolve($organization, $quote);

        return Inertia::render('quotes/review', [
            'quote' => $offer->only(['id', 'organization_id', 'organization_name', 'season', 'status', 'customer', 'services', 'payments', 'expires_at', 'accepted_at']),
            'signature' => $request->query('signature'), 'expires' => $request->query('expires'),
        ]);
    }

    public function store(AcceptQuoteRequest $request, string $organization, string $quote, AcceptQuote $accept): RedirectResponse
    {
        $offer = $this->resolve($organization, $quote);
        $accept->handle($offer);

        return redirect($offer->reviewUrl());
    }

    private function resolve(string $organization, string $quote): Quote
    {
        $offer = Quote::query()->withoutGlobalScopes()->where('organization_id', $organization)->findOrFail($quote);
        abort_unless(in_array($offer->status, ['sent', 'accepted'], true), 410);
        abort_unless(Client::query()->withoutGlobalScopes()->where('organization_id', $organization)->whereNotAnonymized()->whereKey($offer->client_id)->exists(), 404);
        abort_if($offer->expires_at === null || $offer->expires_at->isPast(), 410);

        return $offer;
    }
}
