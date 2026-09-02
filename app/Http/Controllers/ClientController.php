<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\CreateClient;
use App\Actions\DeleteClient;
use App\Actions\UpdateClient;
use App\Enums\ClientType;
use App\Http\Requests\CreateClientRequest;
use App\Http\Requests\UpdateClientRequest;
use App\Models\Client;
use App\Models\Contract;
use App\Support\TenantCache;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection as BaseCollection;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

final readonly class ClientController
{
    public function __construct(private TenantCache $cache) {}

    public function index(Request $request): Response
    {
        /** @var array{search?: string, view?: string, sort?: string, direction?: string}  $validated */
        $validated = $request->validate([
            'search' => ['nullable', 'string', 'max:255'],
            // Anonymized clients are hidden unless the caller asks for
            // them — they surface only for accountability reviews...
            'view' => ['nullable', 'string', Rule::in(['active', 'anonymized', 'all'])],
            'sort' => ['nullable', 'string', Rule::in($this->sortableColumns())],
            'direction' => ['nullable', 'string', Rule::in(['asc', 'desc'])],
        ]);

        $search = $validated['search'] ?? null;
        $view = $validated['view'] ?? 'active';
        $view = in_array($view, ['active', 'anonymized', 'all'], true) ? $view : 'active';
        $sort = in_array($validated['sort'] ?? null, $this->sortableColumns(), true) ? $validated['sort'] : 'created_at';
        $direction = ($validated['direction'] ?? null) === 'asc' ? 'asc' : 'desc';

        $page = max(1, (int) $request->query('page', '1'));
        $perPage = 15;

        // The whole result page — rows and pagination meta — is cached per
        // filter combination; mutations flush the organization's cache.
        /** @var array{rows: list<array<string, mixed>>, total: int} $payload */
        $payload = $this->cache->remember(
            'clients-index',
            [$view, (string) $search, (string) $sort, $direction, (string) $page],
            fn (): array => $this->indexPage($view, $search, $sort, $direction, $page, $perPage),
            TenantCache::TTL_SHORT,
        );

        $clients = new LengthAwarePaginator(
            $payload['rows'],
            $payload['total'],
            $perPage,
            $page,
        );
        $clients->withQueryString();

        return Inertia::render('clients/index', [
            'clients' => $clients,
            'filters' => [
                'search' => $search ?? '',
                'view' => $view,
                'sort' => $sort,
                'direction' => $direction,
            ],
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('clients/create');
    }

    public function store(CreateClientRequest $request, CreateClient $action): RedirectResponse
    {
        /** @var array{type: ClientType|string, companyName?: string|null, firstName: string, lastName: string, email: string, phoneDay?: string|null, cellPhone?: string|null, addressStreet: string, addressCity: string, addressPostalCode: string, addressCountry: string, remark?: string|null}  $attributes */
        $attributes = $request->safe()->all();

        $action->handle($attributes);

        return to_route('clients.index')->with('status', __('statuses.client_created'));
    }

    public function show(Client $client): Response
    {
        /** @var Collection<int, Contract>  $contracts */
        $contracts = $client->contracts()
            ->withSum('services', 'amount')
            ->withSum(['payments as paid_sum' => fn (Builder $query): Builder => $query->where('paid', true)], 'amount')
            ->orderByDesc('season')
            ->get();

        return Inertia::render('clients/show', [
            'client' => [
                'id' => $client->id,
                'clientNumber' => $client->clientNumber,
                'type' => $client->type,
                'companyName' => $client->companyName,
                'firstName' => $client->firstName,
                'lastName' => $client->lastName,
                'email' => $client->email,
                'phoneDay' => $client->phoneDay,
                'cellPhone' => $client->cellPhone,
                'remark' => $client->remark,
                'address' => [
                    'street' => $client->address->street,
                    'city' => $client->address->city,
                    'postalCode' => $client->address->postalCode,
                    'country' => $client->address->country,
                ],
                'contractsCount' => $contracts->count(),
                'contracts' => $this->contractSummaries($contracts),
                'anonymizedAt' => $client->anonymized_at?->toDateString(),
                'created_at' => $client->created_at->toDateString(),
            ],
        ]);
    }

    public function edit(Client $client): Response
    {
        return Inertia::render('clients/edit', [
            'client' => [
                'id' => $client->id,
                'type' => $client->type,
                'companyName' => $client->companyName,
                'firstName' => $client->firstName,
                'lastName' => $client->lastName,
                'email' => $client->email,
                'phoneDay' => $client->phoneDay,
                'cellPhone' => $client->cellPhone,
                'remark' => $client->remark,
                'addressStreet' => $client->address->street,
                'addressCity' => $client->address->city,
                'addressPostalCode' => $client->address->postalCode,
                'addressCountry' => $client->address->country,
                'clientNumber' => $client->clientNumber,
            ],
        ]);
    }

    public function update(UpdateClientRequest $request, Client $client, UpdateClient $action): RedirectResponse
    {
        /** @var array{type: ClientType|string, companyName?: string|null, firstName: string, lastName: string, email: string, phoneDay?: string|null, cellPhone?: string|null, addressStreet: string, addressCity: string, addressPostalCode: string, addressCountry: string, remark?: string|null}  $attributes */
        $attributes = $request->safe()->all();

        $action->handle($client, $attributes);

        return to_route('clients.show', ['client' => $client->id])->with('status', __('statuses.client_updated'));
    }

    public function destroy(Client $client, DeleteClient $action): RedirectResponse
    {
        $action->handle($client);

        return to_route('clients.index')->with('status', __('statuses.client_deleted'));
    }

    /**
     * Query one page of clients and flatten it to a cacheable payload.
     *
     * @param  'active'|'anonymized'|'all'  $view
     * @param  'asc'|'desc'  $direction
     * @return array{rows: list<array{id: string, clientNumber: string, type: ClientType, companyName: string|null, firstName: string, lastName: string, email: string|null, anonymized_at: string|null, created_at: string}>, total: int}
     */
    private function indexPage(string $view, ?string $search, string $sort, string $direction, int $page, int $perPage): array
    {
        /** @var LengthAwarePaginator<int, Client> $clients */
        $clients = Client::query()
            ->when($view === 'active', fn (Builder $query): Builder => $query->whereNotAnonymized())
            ->when($view === 'anonymized', fn (Builder $query): Builder => $query->whereAnonymized())
            ->when($search !== null, fn (Builder $query): Builder => $query->where(
                fn (Builder $query): Builder => $query
                    ->where('clientNumber', 'like', sprintf('%%%s%%', $search))
                    ->orWhere('firstName', 'like', sprintf('%%%s%%', $search))
                    ->orWhere('lastName', 'like', sprintf('%%%s%%', $search))
                    ->orWhere('companyName', 'like', sprintf('%%%s%%', $search))
                    ->orWhere('email', 'like', sprintf('%%%s%%', $search))
            ))
            ->orderBy($sort, $direction)
            ->paginate(perPage: $perPage, page: $page);

        /** @var list<array{id: string, clientNumber: string, type: ClientType, companyName: string|null, firstName: string, lastName: string, email: string|null, anonymized_at: string|null, created_at: string}> $rows */
        $rows = $clients->map(fn (Client $client): array => [
            'id' => $client->id,
            'clientNumber' => $client->clientNumber,
            'type' => $client->type,
            'companyName' => $client->companyName,
            'firstName' => $client->firstName,
            'lastName' => $client->lastName,
            'email' => $client->email,
            'anonymized_at' => $client->anonymized_at?->toDateString(),
            'created_at' => $client->created_at->toDateString(),
        ])->all();

        return [
            'rows' => $rows,
            'total' => $clients->total(),
        ];
    }

    /**
     * @return list<string>
     */
    private function sortableColumns(): array
    {
        return ['clientNumber', 'firstName', 'lastName', 'type', 'created_at'];
    }

    /**
     * One row per contract, mirroring the shape the contracts index
     * displays so the client page can reuse the same visual language.
     *
     * @param  Collection<int, Contract>  $contracts
     * @return BaseCollection<int, array{id: string, season: string, total: int, collected: int, outstanding: int, created_at: string}>
     */
    private function contractSummaries(Collection $contracts): BaseCollection
    {
        return $contracts->map(fn (Contract $contract): array => [
            'id' => $contract->id,
            'season' => $contract->season,
            'total' => (int) $contract->services_sum_amount,
            'collected' => (int) $contract->paid_sum,
            'outstanding' => (int) $contract->services_sum_amount - (int) $contract->paid_sum,
            'created_at' => $contract->created_at->toDateString(),
        ]);
    }
}
