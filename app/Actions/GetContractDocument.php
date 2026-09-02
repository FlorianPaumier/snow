<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\PaymentSlot;
use App\Models\Client;
use App\Models\Contract;
use App\Models\ContractService;
use App\Models\Payment;
use Illuminate\Support\Collection;

final readonly class GetContractDocument
{
    /**
     * Build the view data for the printable contract document, shared by
     * the on-screen document page and the queued PDF generation.
     *
     * @return array<string, mixed>
     */
    public function handle(Contract $contract): array
    {
        $contract->load(['client', 'services', 'payments', 'organization']);

        /** @var Collection<int, ContractService> $services */
        $services = $contract->services;

        /** @var Collection<int, Payment> $payments */
        $payments = $contract->payments;

        $total = $services->sum(fn (ContractService $service): int => $service->amount);
        $collected = $payments->where('paid', true)->sum(fn (Payment $payment): int => $payment->amount);

        return [
            // Overridden to true by the on-screen document page only; the
            // PDF render must never include the print toolbar...
            'toolbar' => false,
            'issuedAt' => now(),
            'organization' => [
                'name' => $contract->organization->name,
            ],
            'client' => $this->clientData($contract->client),
            'contract' => [
                'id' => $contract->id,
                'season' => $contract->season,
                'createdAt' => $contract->created_at,
            ],
            'services' => $services->map(fn (ContractService $service): array => [
                'label' => $service->label,
                'amount' => $service->amount,
            ])->all(),
            'payments' => $this->orderedPayments($contract)->map(fn (Payment $payment): array => [
                'slot' => $payment->slot->value,
                'amount' => $payment->amount,
                'paid' => $payment->paid,
                'method' => $payment->method?->value,
                'paidAt' => $payment->paid_at,
            ])->all(),
            'total' => $total,
            'collected' => $collected,
            'balance' => $total - $collected,
        ];
    }

    /**
     * @return array{number: string, name: string, address: array{street: string, city: string, postalCode: string, country: string}, phone: string|null, email: string|null}
     */
    private function clientData(Client $client): array
    {
        return [
            'number' => $client->clientNumber,
            'name' => $client->displayName(),
            'address' => [
                'street' => $client->address->street,
                'city' => $client->address->city,
                'postalCode' => $client->address->postalCode,
                'country' => $client->address->country,
            ],
            'phone' => $client->cellPhone ?? $client->phoneDay,
            'email' => $client->email,
        ];
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
}
