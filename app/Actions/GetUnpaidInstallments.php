<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\ClientType;
use App\Enums\PaymentSlot;
use App\Models\Client;
use App\Models\Payment;
use App\Support\TenantCache;
use App\ValueObjects\Season;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

final readonly class GetUnpaidInstallments
{
    public function __construct(private TenantCache $cache) {}

    /**
     * Unpaid installments of the given slot for a season, with the client
     * details needed to follow up: name, address, phone and email, plus the
     * contract to jump to its page and the reminder state for the
     * one-click reminder button.
     *
     * @return Collection<int, array{id: int, amount: int, slot: PaymentSlot, contract_id: string, client: array{id: string, displayName: string, clientNumber: string, address_street: string, address_city: string, phone: string|null, email: string|null}, dueDate: string|null, lastReminderAt: string|null}>
     */
    public function handle(Season $season, PaymentSlot $slot): Collection
    {
        /** @var Collection<int, array{id: int, amount: int, slot: PaymentSlot, contract_id: string, client: array{id: string, displayName: string, clientNumber: string, address_street: string, address_city: string, phone: string|null, email: string|null}, dueDate: string|null, lastReminderAt: string|null}> */
        return $this->cache->remember(
            'unpaid-installments',
            [$season->label, $slot->value],
            fn (): Collection => $this->compute($season, $slot),
            TenantCache::TTL_LONG,
        );
    }

    /**
     * @return Collection<int, array{id: int, amount: int, slot: PaymentSlot, contract_id: string, client: array{id: string, displayName: string, clientNumber: string, address_street: string, address_city: string, phone: string|null, email: string|null}, dueDate: string|null, lastReminderAt: string|null}>
     */
    private function compute(Season $season, PaymentSlot $slot): Collection
    {
        /** @var Collection<int, Payment> $payments */
        $payments = Payment::query()
            ->where('slot', $slot)
            ->where('paid', false)
            ->whereHas('contract', fn (Builder $query): Builder => $query->where('season', $season->label))
            ->with('contract.client')
            ->orderBy('id')
            ->get();

        return $payments->map(fn (Payment $payment): array => ['id' => $payment->id,
            'amount' => $payment->amount,
            'slot' => $payment->slot,
            'contract_id' => $payment->contract_id,
            'client' => [
                'id' => $payment->contract->client->id,
                'displayName' => $this->displayName($payment->contract->client),
                'clientNumber' => $payment->contract->client->clientNumber,
                'address_street' => $payment->contract->client->address_street,
                'address_city' => $payment->contract->client->address_city,
                'phone' => $payment->contract->client->phoneDay ?? $payment->contract->client->cellPhone,
                'email' => $payment->contract->client->email,
            ],
            'dueDate' => $payment->due_date?->toDateString(),
            'lastReminderAt' => $payment->last_reminder_at?->toDateString(),
        ]);
    }

    /**
     * Same convention as the contract and client pages.
     */
    private function displayName(Client $client): string
    {
        if ($client->type === ClientType::CLIENT_COMPANY && $client->companyName !== null) {
            return $client->companyName;
        }

        return sprintf('%s %s', $client->firstName, $client->lastName);
    }
}
