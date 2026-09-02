<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\CreateContract;
use App\Actions\DeleteContract;
use App\Actions\UpdateContract;
use App\Enums\PaymentMethod;
use App\Enums\PaymentSlot;
use App\Http\Requests\CreateContractRequest;
use App\Http\Requests\UpdateContractRequest;
use App\Models\Client;
use App\Models\Contract;
use App\Models\ContractService;
use App\Models\Payment;
use App\Support\TenantCache;
use App\ValueObjects\Season;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

final readonly class ContractController
{
    public function __construct(private TenantCache $cache) {}

    public function index(Request $request): Response
    {
        /** @var Collection<int, string> $availableSeasons */
        $availableSeasons = $this->seasons();

        /** @var array{season?: string, client?: string, search?: string, sort?: string, direction?: string} $validated */
        $validated = $request->validate([
            'season' => ['nullable', 'string', Rule::in($availableSeasons->all())],
            'client' => ['nullable', 'uuid'],
            'search' => ['nullable', 'string', 'max:255'],
            'sort' => ['nullable', 'string', Rule::in($this->sortableColumns())],
            'direction' => ['nullable', 'string', Rule::in(['asc', 'desc'])],
        ]);

        $season = $validated['season'] ?? null;
        $clientId = $validated['client'] ?? null;
        $search = $validated['search'] ?? null;
        $sort = in_array($validated['sort'] ?? null, $this->sortableColumns(), true) ? $validated['sort'] : 'created_at';
        $direction = ($validated['direction'] ?? null) === 'asc' ? 'asc' : 'desc';

        $page = max(1, (int) $request->query('page', '1'));
        $perPage = 15;

        /** @var array{rows: list<array<string, mixed>>, total: int} $payload */
        $payload = $this->cache->remember(
            'contracts-index',
            [(string) $season, (string) $clientId, (string) $search, $sort, $direction, (string) $page],
            fn (): array => $this->indexPage($season, $clientId, $search, $sort, $direction, $page, $perPage),
            TenantCache::TTL_SHORT,
        );

        $contracts = new LengthAwarePaginator(
            $payload['rows'],
            $payload['total'],
            $perPage,
            $page,
        );
        $contracts->withQueryString();

        return Inertia::render('contract/index', [
            'contracts' => $contracts,
            'filters' => [
                'season' => $season ?? '',
                'client' => $clientId ?? '',
                'search' => $search ?? '',
                'sort' => $sort,
                'direction' => $direction,
            ],
            'seasons' => $availableSeasons->all(),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('contract/create', [
            'clients' => $this->clientOptions(),
            'seasons' => $this->seasonOptions(),
            'slots' => $this->slotOptions(),
        ]);
    }

    public function store(CreateContractRequest $request, CreateContract $action): RedirectResponse
    {
        /** @var array{clientId: string, season: string, services: list<array{label: string, amount: int}>, payments: array<string, array{amount: int, dueDate: string|null}>}  $attributes */
        $attributes = $request->safe()->all();

        // Resolve through the scoped query so contracts can only ever be
        // created for the current organization's clients...
        $client = Client::query()->findOrFail($attributes['clientId']);

        $contract = $action->handle([
            'client' => $client,
            'season' => $attributes['season'],
            'services' => $attributes['services'],
            'payments' => $attributes['payments'],
        ]);

        return to_route('contracts.show', ['contract' => $contract->id])->with('status', __('statuses.contract_created'));
    }

    public function show(Contract $contract): Response
    {
        $contract->load(['client', 'services', 'payments']);

        /** @var Collection<int, ContractService>  $services */
        $services = $contract->services;
        /** @var Collection<int, Payment>  $payments */
        $payments = $contract->payments;

        $total = $services->sum(fn (ContractService $service): int => $service->amount);
        $collected = $payments->where('paid', true)->sum(fn (Payment $payment): int => $payment->amount);

        return Inertia::render('contract/show', [
            'contract' => [
                'id' => $contract->id,
                'season' => $contract->season,
                'client' => [
                    'id' => $contract->client->id,
                    'clientNumber' => $contract->client->clientNumber,
                    'displayName' => $contract->client->displayName(),
                ],
                'services' => $contract->services->map(fn (ContractService $service): array => [
                    'id' => $service->id,
                    'label' => $service->label,
                    'amount' => $service->amount,
                    'position' => $service->position,
                ])->all(),
                'payments' => $this->orderedPayments($contract)->map(fn (Payment $payment): array => [
                    'id' => $payment->id,
                    'slot' => $payment->slot->value,
                    'slotLabel' => $payment->slot->label(),
                    'amount' => $payment->amount,
                    'paid' => $payment->paid,
                    'method' => $payment->method?->value,
                    'methodLabel' => $payment->method?->label(),
                    'paidAt' => $payment->paid_at?->toDateString(),
                ])->all(),
                'total' => $total,
                'collected' => $collected,
                'outstanding' => $total - $collected,
                'created_at' => $contract->created_at->toDateString(),
            ],
            'methods' => $this->methodOptions(),
        ]);
    }

    public function edit(Contract $contract): Response
    {
        $contract->load(['services', 'payments']);

        return Inertia::render('contract/edit', [
            'contract' => [
                'id' => $contract->id,
                'clientId' => $contract->client_id,
                'season' => $contract->season,
                'services' => $contract->services->map(fn (ContractService $service): array => [
                    'id' => $service->id,
                    'label' => $service->label,
                    'amount' => $service->amount,
                ])->all(),
                'payments' => $this->orderedPayments($contract)->mapWithKeys(fn (Payment $payment): array => [
                    $payment->slot->value => [
                        'amount' => $payment->amount,
                        'paid' => $payment->paid,
                        'dueDate' => $payment->due_date?->toDateString(),
                    ],
                ])->all(),
            ],
            'clients' => $this->clientOptions(),
            'seasons' => $this->seasonOptions(),
            'slots' => $this->slotOptions(),
        ]);
    }

    public function update(UpdateContractRequest $request, Contract $contract, UpdateContract $action): RedirectResponse
    {
        /** @var array{clientId: string, season: string, services: list<array{id?: int, label: string, amount: int}>, payments: array<string, array{amount: int, dueDate: string|null}>}  $attributes */
        $attributes = $request->safe()->all();

        $action->handle($contract, $attributes);

        return to_route('contracts.show', ['contract' => $contract->id])->with('status', __('statuses.contract_updated'));
    }

    public function destroy(Contract $contract, DeleteContract $action): RedirectResponse
    {
        $action->handle($contract);

        return to_route('contracts.index')->with('status', __('statuses.contract_deleted'));
    }

    /**
     * Query one page of contracts and flatten it to a cacheable payload.
     *
     * @param  'asc'|'desc'  $direction
     * @return array{rows: list<array{id: string, season: string, client: array{id: string, clientNumber: string, displayName: string}, total: int, collected: int, outstanding: int, created_at: string}>, total: int}
     */
    private function indexPage(?string $season, ?string $clientId, ?string $search, string $sort, string $direction, int $page, int $perPage): array
    {
        /** @var LengthAwarePaginator<int, Contract> $contracts */
        $contracts = Contract::query()
            ->with('client')
            ->withSum('services', 'amount')
            ->withSum(['payments as paid_sum' => fn (Builder $query): Builder => $query->where('paid', true)], 'amount')
            ->when($season !== null, fn (Builder $query): Builder => $query->where('season', $season))
            ->when($clientId !== null, fn (Builder $query): Builder => $query->where('client_id', $clientId))
            ->when($search !== null, fn (Builder $query): Builder => $query->where(
                fn (Builder $query): Builder => $query->whereHas('client', fn (Builder $clientQuery): Builder => $clientQuery
                    ->where('firstName', 'like', sprintf('%%%s%%', $search))
                    ->orWhere('lastName', 'like', sprintf('%%%s%%', $search))
                    ->orWhere('companyName', 'like', sprintf('%%%s%%', $search))
                    ->orWhere('clientNumber', 'like', sprintf('%%%s%%', $search)))
            ))
            ->orderBy($sort, $direction)
            ->paginate(perPage: $perPage, page: $page);

        /** @var list<array{id: string, season: string, client: array{id: string, clientNumber: string, displayName: string}, total: int, collected: int, outstanding: int, created_at: string}> $rows */
        $rows = $contracts->map(fn (Contract $contract): array => [
            'id' => $contract->id,
            'season' => $contract->season,
            'client' => [
                'id' => $contract->client->id,
                'clientNumber' => $contract->client->clientNumber,
                'displayName' => $contract->client->displayName(),
            ],
            'total' => (int) $contract->services_sum_amount,
            'collected' => (int) $contract->paid_sum,
            'outstanding' => (int) $contract->services_sum_amount - (int) $contract->paid_sum,
            'created_at' => $contract->created_at->toDateString(),
        ])->all();

        return [
            'rows' => $rows,
            'total' => $contracts->total(),
        ];
    }

    /**
     * @return list<array{value: string, label: string}>
     */
    private function methodOptions(): array
    {
        /** @var list<array{value: string, label: string}>  $options */
        $options = [];

        foreach (PaymentMethod::cases() as $method) {
            $options[] = [
                'value' => $method->value,
                'label' => $method->label(),
            ];
        }

        return $options;
    }

    /**
     * @return list<array{id: string, clientNumber: string, displayName: string}>
     */
    private function clientOptions(): array
    {
        /** @var list<array{id: string, clientNumber: string, displayName: string}> */
        return $this->cache->remember(
            'client-options',
            [],
            function (): array {
                /** @var Collection<int, Client> $clients */
                $clients = Client::query()
                    ->orderBy('lastName')
                    ->orderBy('firstName')
                    ->get();

                /** @var list<array{id: string, clientNumber: string, displayName: string}> $options */
                $options = [];

                foreach ($clients as $client) {
                    $options[] = [
                        'id' => $client->id,
                        'clientNumber' => $client->clientNumber,
                        'displayName' => $client->displayName(),
                    ];
                }

                return $options;
            },
            TenantCache::TTL_SHORT,
        );
    }

    /**
     * Seasons that have contracts, newest first — shared by the index
     * filter list and the dashboard's season picker.
     *
     * @return Collection<int, string>
     */
    private function seasons(): Collection
    {
        /** @var Collection<int, string> */
        return $this->cache->remember(
            'seasons',
            [],
            fn (): Collection => Contract::query()
                ->distinct()
                ->orderByDesc('season')
                ->pluck('season'),
            TenantCache::TTL_SHORT,
        );
    }

    /**
     * @return list<string>
     */
    private function seasonOptions(): array
    {
        $current = Season::current();

        return [
            $current->previous()->label,
            $current->label,
            $current->next()->label,
        ];
    }

    /**
     * @return list<array{value: string, label: string}>
     */
    private function slotOptions(): array
    {
        /** @var list<array{value: string, label: string}>  $options */
        $options = [];

        foreach (PaymentSlot::cases() as $slot) {
            $options[] = [
                'value' => $slot->value,
                'label' => $slot->label(),
            ];
        }

        return $options;
    }

    /**
     * Payments ordered by their slot's natural order (Dépôt → Solde).
     *
     * @return Collection<int, Payment>
     */
    private function orderedPayments(Contract $contract): Collection
    {
        $order = collect(PaymentSlot::cases())
            ->map(fn (PaymentSlot $slot): string => $slot->value)
            ->all();

        return $contract->payments
            ->sortBy(fn (Payment $payment): int|false => array_search($payment->slot->value, $order, true))
            ->values();
    }

    /**
     * @return list<string>
     */
    private function sortableColumns(): array
    {
        return ['season', 'created_at'];
    }
}
