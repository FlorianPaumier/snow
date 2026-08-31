<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\ClientType;
use App\Enums\PaymentSlot;
use App\Models\Client;
use App\Models\Payment;
use App\ValueObjects\Season;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

final readonly class GetUnpaidInstallments
{
    /**
     * Unpaid installments of the given slot for a season, with the client
     * details needed to follow up: name, address and phone, plus the
     * contract to jump to its page.
     *
     * @return Collection<int, array{id: int, amount: int, slot: PaymentSlot, contract_id: string, client: array{id: string, displayName: string, clientNumber: string, address_street: string, address_city: string, phone: string|null}}>
     */
    public function handle(Season $season, PaymentSlot $slot): Collection
    {
        /** @var Collection<int, Payment> $payments */
        $payments = Payment::query()
            ->where('slot', $slot)
            ->where('paid', false)
            ->whereHas('contract', fn (Builder $query): Builder => $query->where('season', $season->label))
            ->with('contract.client')
            ->orderBy('id')
            ->get();

        return $payments->map(fn (Payment $payment): array => [
            'id' => $payment->id,
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
            ],
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
