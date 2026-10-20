<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\CreateQuote;
use App\Actions\UpdateQuote;
use App\Enums\PaymentSlot;
use App\Http\Requests\StoreQuoteRequest;
use App\Models\CatalogService;
use App\Models\Client;
use App\Models\Quote;
use App\ValueObjects\Season;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

final readonly class QuoteController
{
    public function index(): Response
    {
        return Inertia::render('quotes/index', ['quotes' => Quote::query()->with('client')->latest()->paginate(15)->through(fn (Quote $quote): array => [
            'id' => $quote->id, 'season' => $quote->season, 'status' => $quote->status,
            'name' => $quote->customer['name'] ?? $quote->client->displayName(),
            'total' => array_sum(array_column($quote->services, 'amount')),
        ])]);
    }

    public function create(): Response
    {
        return Inertia::render('quotes/form', $this->options());
    }

    public function store(StoreQuoteRequest $request, CreateQuote $create): RedirectResponse
    {
        $quote = $create->handle($request->user() ?? abort(403), $request->quoteData());

        return to_route('quotes.show', $quote);
    }

    public function show(Quote $quote): Response
    {
        if ($quote->status === 'draft') {
            $client = $quote->client;
            $quote->customer = ['name' => $client->displayName(), 'address' => implode(', ', [$client->address->street, $client->address->city, $client->address->postalCode, $client->address->country]), 'email' => $client->email ?? ''];
            $quote->organization_name = $quote->organization?->name;
        }

        return Inertia::render('quotes/show', ['quote' => $quote->only(['id', 'client_id', 'season', 'status', 'services', 'payments', 'customer', 'organization_name', 'expires_at', 'accepted_at', 'contract_id', 'source_contract_id', 'renewal_reviewed_at'])]);
    }

    public function edit(Quote $quote): Response
    {
        abort_unless($quote->status === 'draft', 409);

        return Inertia::render('quotes/form', [...$this->options(), 'quote' => $quote->only(['id', 'client_id', 'season', 'services', 'payments', 'source_contract_id'])]);
    }

    public function update(StoreQuoteRequest $request, Quote $quote, UpdateQuote $update): RedirectResponse
    {
        $update->handle($request->user() ?? abort(403), $quote, $request->quoteData(), $request->boolean('renewalReviewed'));

        return to_route('quotes.show', $quote);
    }

    /** @return array{catalogServices: Collection<int, CatalogService>, clients: list<array{id: string, clientNumber: string, displayName: string}>, seasons: list<string>, slots: list<array{value: string, label: string}>} */
    private function options(): array
    {
        $current = Season::current();

        return [
            'catalogServices' => CatalogService::query()->orderBy('label')->get(['id', 'label', 'amount']),
            'clients' => array_values(Client::query()->whereNotAnonymized()->orderBy('lastName')->get()->map(fn (Client $client): array => ['id' => $client->id, 'clientNumber' => $client->clientNumber, 'displayName' => $client->displayName()])->all()),
            'seasons' => [$current->previous()->label, $current->label, $current->next()->label],
            'slots' => array_map(fn (PaymentSlot $slot): array => ['value' => $slot->value, 'label' => $slot->label()], PaymentSlot::cases()),
        ];
    }
}
